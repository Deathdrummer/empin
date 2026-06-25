/**
 * Event Bus - Центральная шина событий
 * Обеспечивает слабую связанность между модулями
 */
export class EventBus {
	constructor() {
		this.listeners = new Map();
		this.onceListeners = new Map();
		this.wildcardListeners = new Map();
		this.maxListeners = 100; // Предотвращение утечек памяти
		this.debugMode = false;
	}

	/**
	 * Включает/выключает режим отладки
	 * @param {boolean} enabled - Включить отладку
	 */
	setDebugMode(enabled) {
		this.debugMode = enabled;
	}

	/**
	 * Подписка на событие
	 * @param {string} event - Имя события
	 * @param {Function} callback - Функция обработчик
	 * @param {Object} options - Опции подписки
	 * @returns {Function} Функция отписки
	 */
	on(event, callback, options = {}) {
		this.validateCallback(callback);
		
		// Проверяем лимит подписчиков
		this.checkListenerLimit(event);
		
		if (!this.listeners.has(event)) {
			this.listeners.set(event, []);
		}

		const listener = {
			callback,
			context: options.context || null,
			priority: options.priority || 0,
			id: this.generateListenerId()
		};

		this.listeners.get(event).push(listener);
		
		// Сортируем по приоритету (больше приоритет = раньше выполняется)
		this.listeners.get(event).sort((a, b) => b.priority - a.priority);

		if (this.debugMode) {
			console.log(`EventBus: Subscribed to '${event}' (priority: ${listener.priority})`);
		}

		// Возвращаем функцию отписки
		return () => this.off(event, callback);
	}

	/**
	 * Одноразовая подписка на событие
	 * @param {string} event - Имя события
	 * @param {Function} callback - Функция обработчик
	 * @param {Object} options - Опции подписки
	 * @returns {Function} Функция отписки
	 */
	once(event, callback, options = {}) {
		this.validateCallback(callback);

		if (!this.onceListeners.has(event)) {
			this.onceListeners.set(event, []);
		}

		const listener = {
			callback,
			context: options.context || null,
			priority: options.priority || 0,
			id: this.generateListenerId()
		};

		this.onceListeners.get(event).push(listener);
		this.onceListeners.get(event).sort((a, b) => b.priority - a.priority);

		if (this.debugMode) {
			console.log(`EventBus: Subscribed once to '${event}' (priority: ${listener.priority})`);
		}

		// Возвращаем функцию отписки
		return () => this.offOnce(event, callback);
	}

	/**
	 * Подписка на события по паттерну (wildcard)
	 * @param {string} pattern - Паттерн события (например, "user.*")
	 * @param {Function} callback - Функция обработчик
	 * @param {Object} options - Опции подписки
	 * @returns {Function} Функция отписки
	 */
	onPattern(pattern, callback, options = {}) {
		this.validateCallback(callback);

		if (!this.wildcardListeners.has(pattern)) {
			this.wildcardListeners.set(pattern, []);
		}

		const listener = {
			callback,
			context: options.context || null,
			priority: options.priority || 0,
			pattern: this.createPatternRegex(pattern),
			id: this.generateListenerId()
		};

		this.wildcardListeners.get(pattern).push(listener);

		if (this.debugMode) {
			console.log(`EventBus: Subscribed to pattern '${pattern}'`);
		}

		return () => this.offPattern(pattern, callback);
	}

	/**
	 * Отписка от события
	 * @param {string} event - Имя события
	 * @param {Function} callback - Функция обработчик
	 */
	off(event, callback) {
		if (this.listeners.has(event)) {
			const listeners = this.listeners.get(event);
			const index = listeners.findIndex(l => l.callback === callback);
			
			if (index > -1) {
				listeners.splice(index, 1);
				
				if (listeners.length === 0) {
					this.listeners.delete(event);
				}

				if (this.debugMode) {
					console.log(`EventBus: Unsubscribed from '${event}'`);
				}
			}
		}
	}

	/**
	 * Отписка от одноразового события
	 * @param {string} event - Имя события
	 * @param {Function} callback - Функция обработчик
	 */
	offOnce(event, callback) {
		if (this.onceListeners.has(event)) {
			const listeners = this.onceListeners.get(event);
			const index = listeners.findIndex(l => l.callback === callback);
			
			if (index > -1) {
				listeners.splice(index, 1);
				
				if (listeners.length === 0) {
					this.onceListeners.delete(event);
				}
			}
		}
	}

	/**
	 * Отписка от паттерна событий
	 * @param {string} pattern - Паттерн события
	 * @param {Function} callback - Функция обработчик
	 */
	offPattern(pattern, callback) {
		if (this.wildcardListeners.has(pattern)) {
			const listeners = this.wildcardListeners.get(pattern);
			const index = listeners.findIndex(l => l.callback === callback);
			
			if (index > -1) {
				listeners.splice(index, 1);
				
				if (listeners.length === 0) {
					this.wildcardListeners.delete(pattern);
				}
			}
		}
	}

	/**
	 * Отписка от всех событий определенного типа
	 * @param {string} event - Имя события
	 */
	offAll(event) {
		this.listeners.delete(event);
		this.onceListeners.delete(event);
		
		if (this.debugMode) {
			console.log(`EventBus: Removed all listeners for '${event}'`);
		}
	}

	/**
	 * Отправка события
	 * @param {string} event - Имя события
	 * @param {*} data - Данные события
	 * @param {Object} options - Опции отправки
	 * @returns {boolean} true если событие было обработано
	 */
	emit(event, data = null, options = {}) {
		const { async = false, timeout = 0 } = options;
		let handled = false;

		if (this.debugMode) {
			console.log(`EventBus: Emitting '${event}'`, data);
		}

		if (async) {
			// Асинхронная отправка
			setTimeout(() => {
				this.processEvent(event, data);
			}, timeout);
			return true;
		} else {
			// Синхронная отправка
			return this.processEvent(event, data);
		}
	}

	/**
	 * Обработка события
	 * @param {string} event - Имя события
	 * @param {*} data - Данные события
	 * @returns {boolean} true если событие было обработано
	 */
	processEvent(event, data) {
		let handled = false;
		const eventData = {
			type: event,
			data,
			timestamp: Date.now(),
			preventDefault: false,
			stopPropagation: false
		};

		// Обычные подписчики
		if (this.listeners.has(event)) {
			const listeners = [...this.listeners.get(event)];
			
			for (const listener of listeners) {
				try {
					if (listener.context) {
						listener.callback.call(listener.context, eventData);
					} else {
						listener.callback(eventData);
					}
					handled = true;

					// Проверяем флаг остановки распространения
					if (eventData.stopPropagation) {
						break;
					}
				} catch (error) {
					console.error(`EventBus: Error in listener for '${event}':`, error);
				}
			}
		}

		// Одноразовые подписчики
		if (this.onceListeners.has(event)) {
			const listeners = [...this.onceListeners.get(event)];
			this.onceListeners.delete(event);
			
			for (const listener of listeners) {
				try {
					if (listener.context) {
						listener.callback.call(listener.context, eventData);
					} else {
						listener.callback(eventData);
					}
					handled = true;

					if (eventData.stopPropagation) {
						break;
					}
				} catch (error) {
					console.error(`EventBus: Error in once listener for '${event}':`, error);
				}
			}
		}

		// Wildcard подписчики
		for (const [pattern, listeners] of this.wildcardListeners) {
			for (const listener of listeners) {
				if (listener.pattern.test(event)) {
					try {
						if (listener.context) {
							listener.callback.call(listener.context, eventData);
						} else {
							listener.callback(eventData);
						}
						handled = true;

						if (eventData.stopPropagation) {
							break;
						}
					} catch (error) {
						console.error(`EventBus: Error in wildcard listener for '${pattern}':`, error);
					}
				}
			}
		}

		return handled;
	}

	/**
	 * Создает промис, который разрешается при получении события
	 * @param {string} event - Имя события
	 * @param {number} timeout - Таймаут в миллисекундах
	 * @returns {Promise} Промис с данными события
	 */
	waitFor(event, timeout = 5000) {
		return new Promise((resolve, reject) => {
			const timer = setTimeout(() => {
				this.off(event, handler);
				reject(new Error(`Timeout waiting for event '${event}'`));
			}, timeout);

			const handler = (eventData) => {
				clearTimeout(timer);
				resolve(eventData);
			};

			this.once(event, handler);
		});
	}

	/**
	 * Получает количество подписчиков на событие
	 * @param {string} event - Имя события
	 * @returns {number} Количество подписчиков
	 */
	getListenerCount(event) {
		const regular = this.listeners.has(event) ? this.listeners.get(event).length : 0;
		const once = this.onceListeners.has(event) ? this.onceListeners.get(event).length : 0;
		return regular + once;
	}

	/**
	 * Получает список всех событий с подписчиками
	 * @returns {Array<string>} Массив имен событий
	 */
	getEventNames() {
		const events = new Set();
		
		for (const event of this.listeners.keys()) {
			events.add(event);
		}
		
		for (const event of this.onceListeners.keys()) {
			events.add(event);
		}
		
		return Array.from(events);
	}

	/**
	 * Очищает все подписки
	 */
	clear() {
		this.listeners.clear();
		this.onceListeners.clear();
		this.wildcardListeners.clear();
		
		if (this.debugMode) {
			console.log('EventBus: All listeners cleared');
		}
	}

	/**
	 * Проверяет валидность callback функции
	 * @param {Function} callback - Функция для проверки
	 */
	validateCallback(callback) {
		if (typeof callback !== 'function') {
			throw new Error('EventBus: Callback must be a function');
		}
	}

	/**
	 * Проверяет лимит подписчиков для предотвращения утечек памяти
	 * @param {string} event - Имя события
	 */
	checkListenerLimit(event) {
		const count = this.getListenerCount(event);
		if (count >= this.maxListeners) {
			console.warn(`EventBus: Too many listeners (${count}) for event '${event}'. Possible memory leak.`);
		}
	}

	/**
	 * Генерирует уникальный ID для подписчика
	 * @returns {string} Уникальный ID
	 */
	generateListenerId() {
		return Date.now().toString(36) + Math.random().toString(36).substr(2);
	}

	/**
	 * Создает регулярное выражение из паттерна
	 * @param {string} pattern - Паттерн с wildcard символами
	 * @returns {RegExp} Регулярное выражение
	 */
	createPatternRegex(pattern) {
		const escaped = pattern.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
		const regex = escaped.replace(/\\\*/g, '.*').replace(/\\\?/g, '.');
		return new RegExp(`^${regex}$`);
	}

	/**
	 * Получает статистику использования для отладки
	 * @returns {Object} Статистика EventBus
	 */
	getStats() {
		return {
			totalEvents: this.getEventNames().length,
			regularListeners: this.listeners.size,
			onceListeners: this.onceListeners.size,
			wildcardListeners: this.wildcardListeners.size,
			events: this.getEventNames().map(event => ({
				name: event,
				listenerCount: this.getListenerCount(event)
			}))
		};
	}
}