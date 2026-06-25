 /**
 * State Store - Централизованное управление состоянием приложения
 * Обеспечивает единый источник истины для всех данных
 */
export class StateStore {
	constructor(eventBus) {
		this.eventBus = eventBus;
		this.state = this.createInitialState();
		this.subscribers = new Map();
		this.middleware = [];
		this.history = [];
		this.maxHistorySize = 50;
		this.debugMode = false;
		
		this.initializeStateTracking();
	}

	/**
	 * Создает начальное состояние приложения
	 * @returns {Object} Начальное состояние
	 */
	createInitialState() {
		return {
			// Общие настройки приложения
			app: {
				mode: 'select',
				initialized: false,
				loading: false,
				error: null
			},

			// Состояние Canvas и Paper
			canvas: {
				width: 800,
				height: 600,
				zoom: 1,
				pan: { x: 0, y: 0 },
				gridSize: 10,
				background: '#ffffff'
			},

			// Состояние выделения элементов
			selection: {
				elements: [],
				type: null,
				boundingBox: null,
				lastSelected: null
			},

			// Состояние портов
			ports: {
				visible: new Set(),
				states: new Map(),
				highlighted: null,
				dragTarget: null
			},

			// Состояние соединений
			connections: {
				mode: 1,
				creating: false,
				preview: null,
				router: 'manhattan',
				connector: 'rounded'
			},

			// Состояние пользовательского интерфейса
			ui: {
				contextMenu: {
					visible: false,
					x: 0,
					y: 0,
					target: null,
					items: []
				},
				toolbar: {
					activeTools: new Set(),
					disabled: new Set()
				},
				guidelines: {
					visible: false,
					lines: [],
					snapDistance: 8
				},
				panels: {
					properties: { visible: false, target: null },
					layers: { visible: false, selected: [] }
				}
			},

			// Состояние плагинов
			plugins: {
				callouts: {
					enabled: true,
					editing: null,
					overlays: new Map()
				},
				history: {
					canUndo: false,
					canRedo: false,
					position: -1
				}
			},

			// Данные графа
			graph: {
				elements: new Map(),
				links: new Map(),
				metadata: {
					created: null,
					modified: null,
					version: '1.0'
				}
			}
		};
	}

	/**
	 * Инициализирует отслеживание изменений состояния
	 */
	initializeStateTracking() {
		this.state = new Proxy(this.state, {
			set: (target, property, value) => {
				const oldValue = target[property];
				target[property] = value;
				
				if (this.debugMode) {
					console.log(`StateStore: Property '${property}' changed`, { oldValue, newValue: value });
				}
				
				this.notifySubscribers(property, oldValue, value);
				return true;
			}
		});
	}

	/**
	 * Включает или выключает режим отладки
	 * @param {boolean} enabled - Включить отладку
	 */
	setDebugMode(enabled) {
		this.debugMode = enabled;
	}

	/**
	 * Получает текущее состояние или его часть
	 * @param {string} path - Путь к состоянию (например, 'selection.elements')
	 * @returns {*} Значение состояния
	 */
	get(path = null) {
		if (!path) {
			return this.state;
		}

		return this.getValueByPath(this.state, path);
	}

	/**
	 * Обновляет состояние
	 * @param {string|Object} pathOrUpdates - Путь к состоянию или объект обновлений
	 * @param {*} value - Новое значение (если первый параметр - путь)
	 */
	set(pathOrUpdates, value = undefined) {
		if (typeof pathOrUpdates === 'string') {
			this.setValueByPath(pathOrUpdates, value);
		} else if (typeof pathOrUpdates === 'object') {
			this.setBatch(pathOrUpdates);
		}
	}

	/**
	 * Пакетное обновление состояния
	 * @param {Object} updates - Объект с обновлениями
	 */
	setBatch(updates) {
		const changes = [];
		
		for (const [path, value] of Object.entries(updates)) {
			const oldValue = this.get(path);
			this.setValueByPath(path, value, false);
			changes.push({ path, oldValue, newValue: value });
		}
		
		this.eventBus.emit('state:batch-changed', {
			changes,
			timestamp: Date.now()
		});
		
		if (this.debugMode) {
			console.log('StateStore: Batch update completed', changes);
		}
	}

	/**
	 * Устанавливает значение по пути
	 * @param {string} path - Путь к свойству
	 * @param {*} value - Новое значение
	 * @param {boolean} notify - Отправлять ли уведомления
	 */
	setValueByPath(path, value, notify = true) {
		const keys = path.split('.');
		const lastKey = keys.pop();
		const target = keys.reduce((obj, key) => {
			if (!(key in obj)) {
				obj[key] = {};
			}
			return obj[key];
		}, this.state);

		const oldValue = target[lastKey];
		target[lastKey] = value;

		if (notify) {
			this.eventBus.emit(`state:${path}:changed`, {
				path,
				oldValue,
				newValue: value,
				timestamp: Date.now()
			});
			
			this.eventBus.emit('state:changed', {
				path,
				oldValue,
				newValue: value,
				timestamp: Date.now()
			});
		}
	}

	/**
	 * Получает значение по пути
	 * @param {Object} obj - Объект для поиска
	 * @param {string} path - Путь к свойству
	 * @returns {*} Значение или undefined
	 */
	getValueByPath(obj, path) {
		return path.split('.').reduce((current, key) => {
			return current && current[key] !== undefined ? current[key] : undefined;
		}, obj);
	}

	/**
	 * Подписывается на изменения состояния
	 * @param {string} path - Путь к отслеживаемому свойству
	 * @param {Function} callback - Функция обратного вызова
	 * @returns {Function} Функция отписки
	 */
	subscribe(path, callback) {
		if (!this.subscribers.has(path)) {
			this.subscribers.set(path, new Set());
		}
		
		this.subscribers.get(path).add(callback);
		
		return () => {
			const pathSubscribers = this.subscribers.get(path);
			if (pathSubscribers) {
				pathSubscribers.delete(callback);
				if (pathSubscribers.size === 0) {
					this.subscribers.delete(path);
				}
			}
		};
	}

	/**
	 * Уведомляет подписчиков об изменениях
	 * @param {string} path - Путь к измененному свойству
	 * @param {*} oldValue - Старое значение
	 * @param {*} newValue - Новое значение
	 */
	notifySubscribers(path, oldValue, newValue) {
		const subscribers = this.subscribers.get(path);
		if (subscribers) {
			for (const callback of subscribers) {
				try {
					callback({ path, oldValue, newValue, timestamp: Date.now() });
				} catch (error) {
					console.error(`StateStore: Error in subscriber for '${path}':`, error);
				}
			}
		}
	}

	/**
	 * Добавляет middleware для обработки изменений состояния
	 * @param {Function} middleware - Функция middleware
	 */
	addMiddleware(middleware) {
		if (typeof middleware !== 'function') {
			throw new Error('Middleware must be a function');
		}
		this.middleware.push(middleware);
	}

	/**
	 * Применяет middleware к изменению состояния
	 * @param {Object} change - Объект изменения
	 * @returns {Object} Обработанное изменение
	 */
	applyMiddleware(change) {
		return this.middleware.reduce((acc, middleware) => {
			return middleware(acc, this) || acc;
		}, change);
	}

	/**
	 * Сохраняет текущее состояние в истории
	 */
	saveSnapshot() {
		const snapshot = {
			state: JSON.parse(JSON.stringify(this.state)),
			timestamp: Date.now()
		};
		
		this.history.push(snapshot);
		
		if (this.history.length > this.maxHistorySize) {
			this.history.shift();
		}
		
		if (this.debugMode) {
			console.log('StateStore: Snapshot saved', snapshot.timestamp);
		}
	}

	/**
	 * Восстанавливает состояние из снимка
	 * @param {number} index - Индекс снимка в истории
	 * @returns {boolean} Успешность восстановления
	 */
	restoreSnapshot(index) {
		if (index < 0 || index >= this.history.length) {
			return false;
		}
		
		const snapshot = this.history[index];
		this.state = snapshot.state;
		
		this.eventBus.emit('state:restored', {
			snapshot,
			index,
			timestamp: Date.now()
		});
		
		if (this.debugMode) {
			console.log('StateStore: State restored from snapshot', snapshot.timestamp);
		}
		
		return true;
	}

	/**
	 * Вычисляет производные значения на основе текущего состояния
	 * @returns {Object} Объект с вычисленными значениями
	 */
	getComputedValues() {
		return {
			// Есть ли выделенные элементы
			hasSelection: this.get('selection.elements').length > 0,
			
			// Количество выделенных элементов
			selectionCount: this.get('selection.elements').length,
			
			// Видимы ли порты
			hasVisiblePorts: this.get('ports.visible').size > 0,
			
			// Активен ли режим создания соединений
			isConnecting: this.get('connections.creating'),
			
			// Есть ли ошибки
			hasErrors: this.get('app.error') !== null,
			
			// Готово ли приложение
			isReady: this.get('app.initialized') && !this.get('app.loading')
		};
	}

	/**
	 * Валидирует состояние на корректность
	 * @returns {Array} Массив ошибок валидации
	 */
	validate() {
		const errors = [];
		
		// Проверка обязательных полей
		if (typeof this.get('app.mode') !== 'string') {
			errors.push('app.mode must be a string');
		}
		
		if (!Array.isArray(this.get('selection.elements'))) {
			errors.push('selection.elements must be an array');
		}
		
		if (typeof this.get('canvas.zoom') !== 'number' || this.get('canvas.zoom') <= 0) {
			errors.push('canvas.zoom must be a positive number');
		}
		
		// Проверка диапазонов значений
		const zoom = this.get('canvas.zoom');
		if (zoom < 0.1 || zoom > 10) {
			errors.push('canvas.zoom must be between 0.1 and 10');
		}
		
		return errors;
	}

	/**
	 * Сбрасывает состояние к начальным значениям
	 * @param {Array<string>} paths - Пути для сброса (если не указаны, сбрасывается всё)
	 */
	reset(paths = null) {
		const initialState = this.createInitialState();
		
		if (paths) {
			for (const path of paths) {
				const initialValue = this.getValueByPath(initialState, path);
				this.set(path, initialValue);
			}
		} else {
			this.state = initialState;
			this.eventBus.emit('state:reset', { timestamp: Date.now() });
		}
		
		if (this.debugMode) {
			console.log('StateStore: State reset', paths || 'all');
		}
	}

	/**
	 * Получает статистику использования состояния
	 * @returns {Object} Статистика StateStore
	 */
	getStats() {
		return {
			totalSubscribers: Array.from(this.subscribers.values())
				.reduce((total, set) => total + set.size, 0),
			subscribedPaths: Array.from(this.subscribers.keys()),
			historySize: this.history.length,
			middlewareCount: this.middleware.length,
			stateSize: JSON.stringify(this.state).length,
			computed: this.getComputedValues()
		};
	}

	/**
	 * Очищает все подписки и историю
	 */
	destroy() {
		this.subscribers.clear();
		this.middleware.length = 0;
		this.history.length = 0;
		
		this.eventBus.emit('state:destroyed', { timestamp: Date.now() });
		
		if (this.debugMode) {
			console.log('StateStore: Destroyed');
		}
	}
}