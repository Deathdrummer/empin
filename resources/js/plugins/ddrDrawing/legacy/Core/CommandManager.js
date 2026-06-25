 
/**
 * Command Manager - Система команд с поддержкой undo/redo операций
 * Реализует паттерн Command для всех операций редактора
 */
export class CommandManager {
	constructor(eventBus, stateStore) {
		this.eventBus = eventBus;
		this.stateStore = stateStore;
		this.history = [];
		this.currentIndex = -1;
		this.maxHistorySize = 100;
		this.groupedCommand = null;
		this.batchCommands = [];
		this.debugMode = false;
		
		this.initializeEventListeners();
	}

	/**
	 * Инициализирует обработчики событий
	 */
	initializeEventListeners() {
		// Обновляем состояние истории в StateStore
		this.eventBus.on('command:executed', () => this.updateHistoryState());
		this.eventBus.on('command:undone', () => this.updateHistoryState());
		this.eventBus.on('command:redone', () => this.updateHistoryState());
	}

	/**
	 * Включает или выключает режим отладки
	 * @param {boolean} enabled - Включить отладку
	 */
	setDebugMode(enabled) {
		this.debugMode = enabled;
	}

	/**
	 * Выполняет команду
	 * @param {Command} command - Команда для выполнения
	 * @param {Object} options - Опции выполнения
	 * @returns {boolean} Успешность выполнения
	 */
	execute(command, options = {}) {
		const { 
			skipHistory = false, 
			skipValidation = false,
			groupWith = null 
		} = options;

		// Валидация команды
		if (!skipValidation && !this.validateCommand(command)) {
			this.eventBus.emit('command:validation-failed', { command });
			return false;
		}

		try {
			// Сохраняем состояние перед выполнением
			const stateBefore = this.captureState();
			
			// Выполняем команду
			const result = command.execute();
			
			// Проверяем результат выполнения
			if (result === false) {
				this.eventBus.emit('command:execution-failed', { command });
				return false;
			}

			// Сохраняем состояние после выполнения
			const stateAfter = this.captureState();
			command.setStateSnapshots(stateBefore, stateAfter);

			// Добавляем в историю, если требуется
			if (!skipHistory) {
				this.addToHistory(command, groupWith);
			}

			// Уведомляем о выполнении
			this.eventBus.emit('command:executed', {
				command,
				result,
				canUndo: this.canUndo(),
				canRedo: this.canRedo(),
				timestamp: Date.now()
			});

			if (this.debugMode) {
				console.log(`CommandManager: Executed ${command.constructor.name}`, command);
			}

			return true;

		} catch (error) {
			console.error('CommandManager: Command execution failed:', error);
			this.eventBus.emit('command:execution-error', { command, error });
			return false;
		}
	}

	/**
	 * Отменяет последнюю команду
	 * @returns {boolean} Успешность отмены
	 */
	undo() {
		if (!this.canUndo()) {
			return false;
		}

		try {
			const command = this.history[this.currentIndex];
			
			// Выполняем отмену
			const result = command.undo();
			
			if (result === false) {
				this.eventBus.emit('command:undo-failed', { command });
				return false;
			}

			this.currentIndex--;

			this.eventBus.emit('command:undone', {
				command,
				canUndo: this.canUndo(),
				canRedo: this.canRedo(),
				timestamp: Date.now()
			});

			if (this.debugMode) {
				console.log(`CommandManager: Undone ${command.constructor.name}`, command);
			}

			return true;

		} catch (error) {
			console.error('CommandManager: Undo failed:', error);
			this.eventBus.emit('command:undo-error', { error });
			return false;
		}
	}

	/**
	 * Повторяет отмененную команду
	 * @returns {boolean} Успешность повтора
	 */
	redo() {
		if (!this.canRedo()) {
			return false;
		}

		try {
			this.currentIndex++;
			const command = this.history[this.currentIndex];
			
			// Выполняем повтор
			const result = command.redo();
			
			if (result === false) {
				this.currentIndex--;
				this.eventBus.emit('command:redo-failed', { command });
				return false;
			}

			this.eventBus.emit('command:redone', {
				command,
				canUndo: this.canUndo(),
				canRedo: this.canRedo(),
				timestamp: Date.now()
			});

			if (this.debugMode) {
				console.log(`CommandManager: Redone ${command.constructor.name}`, command);
			}

			return true;

		} catch (error) {
			console.error('CommandManager: Redo failed:', error);
			this.currentIndex--;
			this.eventBus.emit('command:redo-error', { error });
			return false;
		}
	}

	/**
	 * Начинает группировку команд в батч
	 * @param {string} description - Описание группы команд
	 */
	beginBatch(description = 'Batch operation') {
		this.batchCommands = [];
		this.groupedCommand = {
			description,
			commands: this.batchCommands,
			startTime: Date.now()
		};

		if (this.debugMode) {
			console.log(`CommandManager: Started batch operation: ${description}`);
		}
	}

	/**
	 * Завершает группировку команд
	 * @returns {boolean} Успешность завершения
	 */
	endBatch() {
		if (!this.groupedCommand || this.batchCommands.length === 0) {
			this.groupedCommand = null;
			this.batchCommands = [];
			return false;
		}

		// Создаем составную команду
		const batchCommand = new BatchCommand(
			this.groupedCommand.description,
			[...this.batchCommands]
		);

		// Добавляем в историю как одну команду
		this.addToHistory(batchCommand);

		// Очищаем группировку
		this.groupedCommand = null;
		this.batchCommands = [];

		this.eventBus.emit('command:batch-completed', {
			command: batchCommand,
			commandCount: batchCommand.commands.length,
			timestamp: Date.now()
		});

		if (this.debugMode) {
			console.log(`CommandManager: Completed batch with ${batchCommand.commands.length} commands`);
		}

		return true;
	}

	/**
	 * Отменяет текущую группировку команд
	 */
	cancelBatch() {
		if (this.groupedCommand) {
			// Отменяем все команды в обратном порядке
			for (let i = this.batchCommands.length - 1; i >= 0; i--) {
				try {
					this.batchCommands[i].undo();
				} catch (error) {
					console.error('CommandManager: Error cancelling batch command:', error);
				}
			}

			this.groupedCommand = null;
			this.batchCommands = [];

			this.eventBus.emit('command:batch-cancelled', { timestamp: Date.now() });

			if (this.debugMode) {
				console.log('CommandManager: Batch operation cancelled');
			}
		}
	}

	/**
	 * Добавляет команду в историю
	 * @param {Command} command - Команда для добавления
	 * @param {string} groupWith - ID группы для объединения
	 */
	addToHistory(command, groupWith = null) {
		// Если команда выполняется в рамках группировки
		if (this.groupedCommand) {
			this.batchCommands.push(command);
			return;
		}

		// Удаляем команды после текущей позиции
		this.history = this.history.slice(0, this.currentIndex + 1);

		// Проверяем объединение с предыдущей командой
		if (groupWith && this.history.length > 0) {
			const lastCommand = this.history[this.history.length - 1];
			if (lastCommand.groupId === groupWith) {
				// Объединяем команды
				if (lastCommand instanceof BatchCommand) {
					lastCommand.addCommand(command);
				} else {
					const batchCommand = new BatchCommand('Combined operation', [lastCommand, command]);
					batchCommand.groupId = groupWith;
					this.history[this.history.length - 1] = batchCommand;
				}
				return;
			}
		}

		// Добавляем новую команду
		command.groupId = groupWith;
		this.history.push(command);
		this.currentIndex++;

		// Ограничиваем размер истории
		if (this.history.length > this.maxHistorySize) {
			this.history.shift();
			this.currentIndex--;
		}
	}

	/**
	 * Проверяет возможность отмены
	 * @returns {boolean} Можно ли отменить
	 */
	canUndo() {
		return this.currentIndex >= 0 && this.history[this.currentIndex]?.canUndo();
	}

	/**
	 * Проверяет возможность повтора
	 * @returns {boolean} Можно ли повторить
	 */
	canRedo() {
		return this.currentIndex < this.history.length - 1 && 
			   this.history[this.currentIndex + 1]?.canRedo();
	}

	/**
	 * Валидирует команду перед выполнением
	 * @param {Command} command - Команда для валидации
	 * @returns {boolean} Команда валидна
	 */
	validateCommand(command) {
		// Проверяем наличие обязательных методов
		if (typeof command.execute !== 'function') {
			console.error('CommandManager: Command must have execute method');
			return false;
		}

		if (typeof command.undo !== 'function') {
			console.error('CommandManager: Command must have undo method');
			return false;
		}

		// Проверяем валидность команды
		if (typeof command.isValid === 'function' && !command.isValid()) {
			console.error('CommandManager: Command validation failed');
			return false;
		}

		return true;
	}

	/**
	 * Захватывает текущее состояние для отката
	 * @returns {Object} Снимок состояния
	 */
	captureState() {
		return {
			timestamp: Date.now(),
			state: JSON.parse(JSON.stringify(this.stateStore.get()))
		};
	}

	/**
	 * Обновляет состояние истории в StateStore
	 */
	updateHistoryState() {
		this.stateStore.set('plugins.history', {
			canUndo: this.canUndo(),
			canRedo: this.canRedo(),
			position: this.currentIndex,
			totalCommands: this.history.length
		});
	}

	/**
	 * Получает описание команды для отображения
	 * @param {number} index - Индекс команды в истории
	 * @returns {string} Описание команды
	 */
	getCommandDescription(index) {
		if (index < 0 || index >= this.history.length) {
			return null;
		}

		const command = this.history[index];
		return command.getDescription ? command.getDescription() : command.constructor.name;
	}

	/**
	 * Получает историю команд для отображения
	 * @param {number} limit - Максимальное количество команд
	 * @returns {Array} Массив описаний команд
	 */
	getHistory(limit = 10) {
		const start = Math.max(0, this.currentIndex - limit + 1);
		const end = Math.min(this.history.length, this.currentIndex + limit + 1);
		
		return this.history.slice(start, end).map((command, index) => ({
			index: start + index,
			description: this.getCommandDescription(start + index),
			isCurrent: start + index === this.currentIndex,
			canUndo: command.canUndo(),
			canRedo: command.canRedo(),
			timestamp: command.timestamp
		}));
	}

	/**
	 * Очищает всю историю команд
	 */
	clearHistory() {
		this.history = [];
		this.currentIndex = -1;
		this.updateHistoryState();

		this.eventBus.emit('command:history-cleared', { timestamp: Date.now() });

		if (this.debugMode) {
			console.log('CommandManager: History cleared');
		}
	}

	/**
	 * Получает статистику командного менеджера
	 * @returns {Object} Статистика
	 */
	getStats() {
		return {
			totalCommands: this.history.length,
			currentPosition: this.currentIndex,
			canUndo: this.canUndo(),
			canRedo: this.canRedo(),
			memoryUsage: JSON.stringify(this.history).length,
			batchInProgress: !!this.groupedCommand,
			batchSize: this.batchCommands.length
		};
	}

	/**
	 * Уничтожает командный менеджер
	 */
	destroy() {
		this.clearHistory();
		this.cancelBatch();
		
		this.eventBus.emit('command:manager-destroyed', { timestamp: Date.now() });

		if (this.debugMode) {
			console.log('CommandManager: Destroyed');
		}
	}
}

/**
 * Составная команда для группировки множественных операций
 */
class BatchCommand {
	constructor(description, commands = []) {
		this.description = description;
		this.commands = commands;
		this.timestamp = Date.now();
		this.executed = false;
	}

	/**
	 * Выполняет все команды в группе
	 * @returns {boolean} Успешность выполнения
	 */
	execute() {
		try {
			for (const command of this.commands) {
				const result = command.execute();
				if (result === false) {
					// Откатываем выполненные команды
					this.undoExecuted();
					return false;
				}
			}
			this.executed = true;
			return true;
		} catch (error) {
			this.undoExecuted();
			throw error;
		}
	}

	/**
	 * Отменяет все команды в группе
	 * @returns {boolean} Успешность отмены
	 */
	undo() {
		try {
			for (let i = this.commands.length - 1; i >= 0; i--) {
				const result = this.commands[i].undo();
				if (result === false) {
					return false;
				}
			}
			this.executed = false;
			return true;
		} catch (error) {
			console.error('BatchCommand: Undo failed:', error);
			return false;
		}
	}

	/**
	 * Повторяет все команды в группе
	 * @returns {boolean} Успешность повтора
	 */
	redo() {
		return this.execute();
	}

	/**
	 * Отменяет частично выполненные команды
	 */
	undoExecuted() {
		for (let i = this.commands.length - 1; i >= 0; i--) {
			try {
				if (this.commands[i].executed) {
					this.commands[i].undo();
				}
			} catch (error) {
				console.error('BatchCommand: Error undoing executed command:', error);
			}
		}
	}

	/**
	 * Добавляет команду в группу
	 * @param {Command} command - Команда для добавления
	 */
	addCommand(command) {
		this.commands.push(command);
	}

	/**
	 * Проверяет возможность отмены
	 * @returns {boolean} Можно ли отменить
	 */
	canUndo() {
		return this.executed && this.commands.every(cmd => cmd.canUndo());
	}

	/**
	 * Проверяет возможность повтора
	 * @returns {boolean} Можно ли повторить
	 */
	canRedo() {
		return !this.executed && this.commands.every(cmd => cmd.canRedo());
	}

	/**
	 * Получает описание команды
	 * @returns {string} Описание
	 */
	getDescription() {
		return `${this.description} (${this.commands.length} operations)`;
	}
}