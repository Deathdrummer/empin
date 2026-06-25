 
/**
 * Dependency Injection Container
 * Управляет созданием и жизненным циклом сервисов
 */
export class DIContainer {
	constructor() {
		this.services = new Map();
		this.instances = new Map();
		this.resolving = new Set(); // Для предотвращения циклических зависимостей
	}

	/**
	 * Регистрирует сервис как синглтон
	 * @param {string} name - Имя сервиса
	 * @param {Function} constructor - Конструктор класса
	 * @param {Array<string>} dependencies - Массив зависимостей
	 */
	registerSingleton(name, constructor, dependencies = []) {
		this.services.set(name, {
			type: 'singleton',
			constructor,
			dependencies,
			instance: null
		});
		return this;
	}

	/**
	 * Регистрирует транзиентный сервис (создается каждый раз заново)
	 * @param {string} name - Имя сервиса
	 * @param {Function} constructor - Конструктор класса
	 * @param {Array<string>} dependencies - Массив зависимостей
	 */
	registerTransient(name, constructor, dependencies = []) {
		this.services.set(name, {
			type: 'transient',
			constructor,
			dependencies
		});
		return this;
	}

	/**
	 * Регистрирует фабричную функцию
	 * @param {string} name - Имя сервиса
	 * @param {Function} factory - Фабричная функция
	 */
	registerFactory(name, factory) {
		this.services.set(name, {
			type: 'factory',
			factory
		});
		return this;
	}

	/**
	 * Регистрирует готовый экземпляр
	 * @param {string} name - Имя сервиса
	 * @param {*} instance - Экземпляр объекта
	 */
	registerInstance(name, instance) {
		this.instances.set(name, instance);
		return this;
	}

	/**
	 * Получает сервис по имени
	 * @param {string} name - Имя сервиса
	 * @returns {*} Экземпляр сервиса
	 */
	get(name) {
		// Проверяем циклические зависимости
		if (this.resolving.has(name)) {
			throw new Error(`Circular dependency detected for service: ${name}`);
		}

		// Если есть готовый экземпляр
		if (this.instances.has(name)) {
			return this.instances.get(name);
		}

		const service = this.services.get(name);
		if (!service) {
			throw new Error(`Service '${name}' not found`);
		}

		this.resolving.add(name);

		try {
			switch (service.type) {
				case 'singleton':
					return this.resolveSingleton(name, service);
				
				case 'transient':
					return this.resolveTransient(service);
				
				case 'factory':
					return service.factory(this);
				
				default:
					throw new Error(`Unknown service type: ${service.type}`);
			}
		} finally {
			this.resolving.delete(name);
		}
	}

	/**
	 * Разрешает синглтон сервис
	 * @param {string} name - Имя сервиса
	 * @param {Object} service - Описание сервиса
	 * @returns {*} Экземпляр сервиса
	 */
	resolveSingleton(name, service) {
		if (!service.instance) {
			const dependencies = this.resolveDependencies(service.dependencies);
			service.instance = new service.constructor(...dependencies);
		}
		return service.instance;
	}

	/**
	 * Разрешает транзиентный сервис
	 * @param {Object} service - Описание сервиса
	 * @returns {*} Новый экземпляр сервиса
	 */
	resolveTransient(service) {
		const dependencies = this.resolveDependencies(service.dependencies);
		return new service.constructor(...dependencies);
	}

	/**
	 * Разрешает зависимости для сервиса
	 * @param {Array<string>} dependencies - Массив имен зависимостей
	 * @returns {Array} Массив разрешенных зависимостей
	 */
	resolveDependencies(dependencies) {
		return dependencies.map(dep => this.get(dep));
	}

	/**
	 * Проверяет существование сервиса
	 * @param {string} name - Имя сервиса
	 * @returns {boolean} true если сервис зарегистрирован
	 */
	has(name) {
		return this.services.has(name) || this.instances.has(name);
	}

	/**
	 * Получает список всех зарегистрированных сервисов
	 * @returns {Array<string>} Массив имен сервисов
	 */
	getServiceNames() {
		return [
			...Array.from(this.services.keys()),
			...Array.from(this.instances.keys())
		];
	}

	/**
	 * Удаляет сервис из контейнера
	 * @param {string} name - Имя сервиса
	 */
	remove(name) {
		const service = this.services.get(name);
		if (service && service.instance && typeof service.instance.destroy === 'function') {
			service.instance.destroy();
		}
		
		this.services.delete(name);
		this.instances.delete(name);
	}

	/**
	 * Очищает контейнер
	 */
	clear() {
		// Вызываем destroy для всех синглтонов
		for (const [name, service] of this.services) {
			if (service.type === 'singleton' && service.instance) {
				if (typeof service.instance.destroy === 'function') {
					try {
						service.instance.destroy();
					} catch (error) {
						console.warn(`Error destroying service ${name}:`, error);
					}
				}
			}
		}

		// Очищаем коллекции
		this.services.clear();
		this.instances.clear();
		this.resolving.clear();
	}

	/**
	 * Получает информацию о сервисе для отладки
	 * @param {string} name - Имя сервиса
	 * @returns {Object|null} Информация о сервисе
	 */
	getServiceInfo(name) {
		if (this.instances.has(name)) {
			return {
				name,
				type: 'instance',
				hasInstance: true
			};
		}

		const service = this.services.get(name);
		if (!service) {
			return null;
		}

		return {
			name,
			type: service.type,
			dependencies: service.dependencies || [],
			hasInstance: service.type === 'singleton' && !!service.instance
		};
	}

	/**
	 * Получает граф зависимостей для отладки
	 * @returns {Object} Граф зависимостей
	 */
	getDependencyGraph() {
		const graph = {};
		
		for (const [name, service] of this.services) {
			graph[name] = {
				type: service.type,
				dependencies: service.dependencies || [],
				hasInstance: service.type === 'singleton' && !!service.instance
			};
		}

		for (const name of this.instances.keys()) {
			graph[name] = {
				type: 'instance',
				dependencies: [],
				hasInstance: true
			};
		}

		return graph;
	}
}