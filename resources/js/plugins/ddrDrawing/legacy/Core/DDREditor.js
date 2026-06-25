import { DIContainer } from './DIContainer.js';
import { EventBus } from './EventBus.js';
import { StateStore } from './StateStore.js';
import { CommandManager } from './CommandManager.js';

/**
 * DDREditor - Main application class that coordinates all system components
 * Serves as the primary entry point and service coordinator for the drawing editor
 */
export class DDREditor {
	constructor(options = {}) {
		this.options = {
			debugMode: false,
			autoInit: true,
			canvasSelector: '#ddrCanvas',
			containerSelector: '#paper-container',
			maxHistorySize: 100,
			validateDOM: true,
			...options
		};

		this.container = new DIContainer();
		this.eventBus = new EventBus();
		this.initialized = false;
		this.destroyed = false;
		this.services = new Map();
		this.plugins = new Map();
		
		this.setupCoreServices();
		this.bindGlobalEventHandlers();

		if (this.options.autoInit) {
			this.init().catch(error => {
				console.error('DDREditor: Auto-initialization failed:', error);
			});
		}
	}

	/**
	 * Configures and registers all core services in the dependency injection container
	 */
	setupCoreServices() {
		this.container.registerInstance('eventBus', this.eventBus);
		this.container.registerSingleton('stateStore', StateStore, ['eventBus']);
		this.container.registerSingleton('commandManager', CommandManager, ['eventBus', 'stateStore']);

		if (this.options.debugMode) {
			this.eventBus.setDebugMode(true);
			this.container.get('stateStore').setDebugMode(true);
			this.container.get('commandManager').setDebugMode(true);
		}
	}

	/**
	 * Establishes global event handlers for keyboard shortcuts and application-level events
	 */
	bindGlobalEventHandlers() {
		document.addEventListener('keydown', this.handleGlobalKeydown.bind(this));
		document.addEventListener('contextmenu', this.handleContextMenu.bind(this));
		window.addEventListener('beforeunload', this.handleBeforeUnload.bind(this));
		
		this.eventBus.on('editor:request-destroy', () => this.destroy());
		this.eventBus.on('editor:toggle-debug', (event) => this.toggleDebugMode(event.data));
	}

	/**
	 * Initializes the DDR Editor application and all its components
	 * @returns {Promise<DDREditor>} Promise that resolves to the initialized editor instance
	 */
	async init() {
		if (this.initialized) {
			console.warn('DDREditor: Already initialized');
			return this;
		}

		if (this.destroyed) {
			throw new Error('DDREditor: Cannot initialize destroyed instance');
		}

		try {
			this.eventBus.emit('editor:initializing');

			if (this.options.validateDOM) {
				this.validateRequiredDOMElements();
			}

			await this.initializeCoreServices();
			this.updateApplicationState('initialized', true);
			this.setupApplicationReadiness();

			this.initialized = true;
			this.eventBus.emit('editor:initialized', { 
				timestamp: Date.now(),
				services: this.getServiceNames(),
				plugins: Array.from(this.plugins.keys())
			});

			console.log('DDREditor: Successfully initialized');
			return this;

		} catch (error) {
			console.error('DDREditor: Initialization failed:', error);
			this.updateApplicationState('error', error.message);
			this.eventBus.emit('editor:initialization-failed', { error });
			throw error;
		}
	}

	/**
	 * Validates that all required DOM elements are present in the document
	 * @throws {Error} When required DOM elements are missing
	 */
	validateRequiredDOMElements() {
		const requiredElements = [
			{ selector: this.options.canvasSelector, name: 'Canvas' },
			{ selector: this.options.containerSelector, name: 'Paper Container' },
			{ selector: '#add-square-btn', name: 'Add Square Button' },
			{ selector: '#router-selector', name: 'Router Selector' },
			{ selector: '#connector-selector', name: 'Connector Selector' }
		];

		const missingElements = requiredElements.filter(element => {
			const domElement = document.querySelector(element.selector);
			return !domElement;
		});

		if (missingElements.length > 0) {
			const missing = missingElements.map(el => `${el.name} (${el.selector})`).join(', ');
			throw new Error(`Required DOM elements not found: ${missing}`);
		}
	}

	/**
	 * Initializes all core services and establishes their interdependencies
	 */
	async initializeCoreServices() {
		const stateStore = this.container.get('stateStore');
		const commandManager = this.container.get('commandManager');

		stateStore.set('app.loading', true);
		stateStore.set('app.mode', 'select');

		this.services.set('stateStore', stateStore);
		this.services.set('commandManager', commandManager);
		this.services.set('eventBus', this.eventBus);

		commandManager.setMaxHistorySize = this.options.maxHistorySize;

		stateStore.set('app.loading', false);
	}

	/**
	 * Configures application readiness indicators and initial state
	 */
	setupApplicationReadiness() {
		const stateStore = this.getService('stateStore');
		
		stateStore.set('app.initialized', true);
		stateStore.set('canvas.width', this.getCanvasWidth());
		stateStore.set('canvas.height', this.getCanvasHeight());

		this.eventBus.emit('editor:ready', {
			canvasSize: {
				width: stateStore.get('canvas.width'),
				height: stateStore.get('canvas.height')
			}
		});
	}

	/**
	 * Retrieves a service instance from the dependency injection container
	 * @param {string} serviceName - Name of the service to retrieve
	 * @returns {*} Service instance
	 * @throws {Error} When service is not found or editor is not initialized
	 */
	getService(serviceName) {
		if (!this.initialized) {
			throw new Error('DDREditor: Cannot access services before initialization');
		}

		return this.container.get(serviceName);
	}

	/**
	 * Registers a new service with the dependency injection container
	 * @param {string} name - Service name
	 * @param {Function} constructor - Service constructor
	 * @param {Array<string>} dependencies - Service dependencies
	 * @param {string} type - Service type ('singleton', 'transient', 'factory')
	 */
	registerService(name, constructor, dependencies = [], type = 'singleton') {
		if (type === 'singleton') {
			this.container.registerSingleton(name, constructor, dependencies);
		} else if (type === 'transient') {
			this.container.registerTransient(name, constructor, dependencies);
		} else if (type === 'factory') {
			this.container.registerFactory(name, constructor);
		}

		this.services.set(name, null); // Placeholder until instantiated
	}

	/**
	 * Registers and initializes a plugin with the editor
	 * @param {string} name - Plugin name
	 * @param {Object} plugin - Plugin instance
	 * @returns {boolean} Success status
	 */
	registerPlugin(name, plugin) {
		try {
			if (typeof plugin.init === 'function') {
				plugin.init(this);
			}

			this.plugins.set(name, plugin);
			
			this.eventBus.emit('plugin:registered', { 
				name, 
				plugin,
				timestamp: Date.now() 
			});

			return true;
		} catch (error) {
			console.error(`DDREditor: Failed to register plugin '${name}':`, error);
			this.eventBus.emit('plugin:registration-failed', { name, error });
			return false;
		}
	}

	/**
	 * Removes and destroys a registered plugin
	 * @param {string} name - Plugin name
	 * @returns {boolean} Success status
	 */
	unregisterPlugin(name) {
		const plugin = this.plugins.get(name);
		if (!plugin) {
			return false;
		}

		try {
			if (typeof plugin.destroy === 'function') {
				plugin.destroy();
			}

			this.plugins.delete(name);
			
			this.eventBus.emit('plugin:unregistered', { 
				name,
				timestamp: Date.now() 
			});

			return true;
		} catch (error) {
			console.error(`DDREditor: Failed to unregister plugin '${name}':`, error);
			return false;
		}
	}

	/**
	 * Updates application state in the centralized state store
	 * @param {string} key - State key to update
	 * @param {*} value - New value
	 */
	updateApplicationState(key, value) {
		if (this.services.has('stateStore')) {
			const stateStore = this.services.get('stateStore') || this.container.get('stateStore');
			stateStore.set(`app.${key}`, value);
		}
	}

	/**
	 * Handles global keyboard shortcuts and hotkeys
	 * @param {KeyboardEvent} event - Keyboard event
	 */
	handleGlobalKeydown(event) {
		const eventData = {
			key: event.key,
			code: event.code,
			ctrlKey: event.ctrlKey,
			shiftKey: event.shiftKey,
			altKey: event.altKey,
			metaKey: event.metaKey,
			originalEvent: event
		};

		// Ctrl+Z for undo
		if (event.ctrlKey && event.key === 'z' && !event.shiftKey) {
			event.preventDefault();
			const commandManager = this.getService('commandManager');
			commandManager.undo();
			return;
		}

		// Ctrl+Shift+Z or Ctrl+Y for redo
		if ((event.ctrlKey && event.shiftKey && event.key === 'Z') || 
			(event.ctrlKey && event.key === 'y')) {
			event.preventDefault();
			const commandManager = this.getService('commandManager');
			commandManager.redo();
			return;
		}

		// Delete key for removing selected elements
		if (event.key === 'Delete') {
			event.preventDefault();
			this.eventBus.emit('selection:delete-requested', eventData);
			return;
		}

		this.eventBus.emit('keyboard:global-keydown', eventData);
	}

	/**
	 * Handles context menu events on the canvas
	 * @param {MouseEvent} event - Mouse event
	 */
	handleContextMenu(event) {
		const canvasElement = document.querySelector(this.options.canvasSelector);
		if (canvasElement && canvasElement.contains(event.target)) {
			event.preventDefault();
			
			this.eventBus.emit('ui:context-menu-requested', {
				x: event.clientX,
				y: event.clientY,
				target: event.target,
				originalEvent: event
			});
		}
	}

	/**
	 * Handles browser unload events for cleanup
	 * @param {BeforeUnloadEvent} event - Unload event
	 */
	handleBeforeUnload(event) {
		this.eventBus.emit('editor:before-unload', { event });
		
		if (this.hasUnsavedChanges()) {
			const message = 'You have unsaved changes. Are you sure you want to leave?';
			event.returnValue = message;
			return message;
		}
	}

	/**
	 * Determines canvas width from DOM element or default value
	 * @returns {number} Canvas width in pixels
	 */
	getCanvasWidth() {
		const container = document.querySelector(this.options.containerSelector);
		return container ? container.clientWidth : 800;
	}

	/**
	 * Determines canvas height from DOM element or default value
	 * @returns {number} Canvas height in pixels
	 */
	getCanvasHeight() {
		const container = document.querySelector(this.options.containerSelector);
		return container ? container.clientHeight : 600;
	}

	/**
	 * Checks if there are unsaved changes in the editor
	 * @returns {boolean} True if there are unsaved changes
	 */
	hasUnsavedChanges() {
		if (!this.initialized) return false;
		
		try {
			const commandManager = this.getService('commandManager');
			return commandManager.getStats().totalCommands > 0;
		} catch {
			return false;
		}
	}

	/**
	 * Toggles debug mode for all system components
	 * @param {boolean} enabled - Enable or disable debug mode
	 */
	toggleDebugMode(enabled = !this.options.debugMode) {
		this.options.debugMode = enabled;
		
		this.eventBus.setDebugMode(enabled);
		
		if (this.initialized) {
			this.getService('stateStore').setDebugMode(enabled);
			this.getService('commandManager').setDebugMode(enabled);
		}

		this.eventBus.emit('debug:mode-changed', { enabled });
	}

	/**
	 * Retrieves names of all registered services
	 * @returns {Array<string>} Array of service names
	 */
	getServiceNames() {
		return this.container.getServiceNames();
	}

	/**
	 * Retrieves comprehensive system status information
	 * @returns {Object} System status object
	 */
	getSystemStatus() {
		return {
			initialized: this.initialized,
			destroyed: this.destroyed,
			debugMode: this.options.debugMode,
			services: this.getServiceNames(),
			plugins: Array.from(this.plugins.keys()),
			hasUnsavedChanges: this.hasUnsavedChanges(),
			canvasSize: {
				width: this.getCanvasWidth(),
				height: this.getCanvasHeight()
			},
			stats: this.initialized ? {
				eventBus: this.eventBus.getStats(),
				stateStore: this.getService('stateStore').getStats(),
				commandManager: this.getService('commandManager').getStats()
			} : null
		};
	}

	/**
	 * Destroys the editor instance and cleans up all resources
	 */
	destroy() {
		if (this.destroyed) {
			return;
		}

		this.eventBus.emit('editor:destroying');

		// Destroy all plugins
		for (const [name, plugin] of this.plugins) {
			this.unregisterPlugin(name);
		}

		// Destroy core services
		if (this.initialized) {
			try {
				this.getService('commandManager').destroy();
				this.getService('stateStore').destroy();
			} catch (error) {
				console.error('DDREditor: Error during service cleanup:', error);
			}
		}

		// Clear dependency injection container
		this.container.clear();

		// Clear event bus
		this.eventBus.clear();

		// Remove global event listeners
		document.removeEventListener('keydown', this.handleGlobalKeydown.bind(this));
		document.removeEventListener('contextmenu', this.handleContextMenu.bind(this));
		window.removeEventListener('beforeunload', this.handleBeforeUnload.bind(this));

		// Clear collections
		this.services.clear();
		this.plugins.clear();

		this.initialized = false;
		this.destroyed = true;

		console.log('DDREditor: Successfully destroyed');
	}
}