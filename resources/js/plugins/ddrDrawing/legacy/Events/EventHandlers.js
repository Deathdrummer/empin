import { 
	EDITOR_EVENTS, 
	GRAPH_EVENTS, 
	ELEMENT_EVENTS, 
	LINK_EVENTS,
	PAPER_EVENTS,
	PORT_EVENTS,
	SELECTION_EVENTS,
	COMMAND_EVENTS,
	STATE_EVENTS,
	KEYBOARD_EVENTS,
	UI_EVENTS,
	VALIDATION_EVENTS
} from './EventTypes.js';

/**
 * EventHandlers - Centralized event handler management and coordination
 * Provides high-level event orchestration between services
 */
export class EventHandlers {
	constructor(container) {
		this.container = container;
		this.eventBus = container.get('eventBus');
		this.stateStore = container.get('stateStore');
		this.commandManager = container.get('commandManager');
		this.handlers = new Map();
		this.initialized = false;
		this.debugMode = false;
	}

	/**
	 * Initializes all event handlers
	 */
	init() {
		if (this.initialized) {
			console.warn('EventHandlers: Already initialized');
			return;
		}

		this.setupEditorHandlers();
		this.setupGraphHandlers();
		this.setupPaperHandlers();
		this.setupSelectionHandlers();
		this.setupPortHandlers();
		this.setupCommandHandlers();
		this.setupValidationHandlers();
		this.setupUIHandlers();
		this.setupKeyboardHandlers();
		this.setupStateHandlers();
		
		this.initialized = true;
		this.eventBus.emit(EDITOR_EVENTS.READY);
	}

	/**
	 * Sets up editor lifecycle event handlers
	 */
	setupEditorHandlers() {
		this.addHandler(EDITOR_EVENTS.INITIALIZED, (event) => {
			this.stateStore.set('app.initialized', true);
			this.stateStore.set('app.loading', false);
			
			if (this.debugMode) {
				console.log('Editor initialized with services:', event.services);
			}
		});

		this.addHandler(EDITOR_EVENTS.DESTROYING, () => {
			this.stateStore.set('app.initialized', false);
			this.cleanup();
		});

		this.addHandler(EDITOR_EVENTS.BEFORE_UNLOAD, (event) => {
			const hasUnsavedChanges = this.commandManager.getStats().totalCommands > 0;
			if (hasUnsavedChanges) {
				event.event.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
			}
		});
	}

	/**
	 * Sets up graph-related event handlers
	 */
	setupGraphHandlers() {
		this.addHandler(GRAPH_EVENTS.INITIALIZED, (event) => {
			this.stateStore.set('graph.metadata.created', Date.now());
		});

		this.addHandler(ELEMENT_EVENTS.ADDED, (event) => {
			this.stateStore.updatePath('graph.metadata.elementCount', 
				this.container.get('graphService').graph.getElements().length
			);
		});

		this.addHandler(ELEMENT_EVENTS.REMOVED, (event) => {
			this.stateStore.updatePath('graph.metadata.elementCount', 
				this.container.get('graphService').graph.getElements().length
			);
		});

		this.addHandler(LINK_EVENTS.ADDED, (event) => {
			this.stateStore.updatePath('graph.metadata.linkCount', 
				this.container.get('graphService').graph.getLinks().length
			);
		});

		this.addHandler(LINK_EVENTS.REMOVED, (event) => {
			this.stateStore.updatePath('graph.metadata.linkCount', 
				this.container.get('graphService').graph.getLinks().length
			);
		});

		this.addHandler(ELEMENT_EVENTS.MOVED, (event) => {
			this.stateStore.set('graph.metadata.modified', Date.now());
		});
	}

	/**
	 * Sets up paper interaction event handlers
	 */
	setupPaperHandlers() {
		this.addHandler(PAPER_EVENTS.BLANK_CLICK, (event) => {
			const appMode = this.stateStore.get('app.mode');
			
			if (appMode === 'add') {
				this.handleElementCreation(event.coordinates);
			} else {
				this.eventBus.emit(SELECTION_EVENTS.CLEAR);
			}
		});

		this.addHandler(PAPER_EVENTS.ELEMENT_CLICK, (event) => {
			const isShiftPressed = event.originalEvent.shiftKey;
			this.eventBus.emit(SELECTION_EVENTS.SELECT_ELEMENT, {
				element: event.element,
				addToSelection: isShiftPressed
			});
		});

		this.addHandler(PAPER_EVENTS.LINK_CLICK, (event) => {
			const isShiftPressed = event.originalEvent.shiftKey;
			this.eventBus.emit(SELECTION_EVENTS.SELECT_ELEMENT, {
				element: event.link,
				addToSelection: isShiftPressed
			});
		});

		this.addHandler(PAPER_EVENTS.ELEMENT_CONTEXTMENU, (event) => {
			this.eventBus.emit(UI_EVENTS.CONTEXT_MENU_REQUESTED, {
				x: event.originalEvent.clientX,
				y: event.originalEvent.clientY,
				target: event.element,
				type: 'element'
			});
		});

		this.addHandler(PAPER_EVENTS.LINK_CONTEXTMENU, (event) => {
			this.eventBus.emit(UI_EVENTS.CONTEXT_MENU_REQUESTED, {
				x: event.originalEvent.clientX,
				y: event.originalEvent.clientY,
				target: event.link,
				type: 'link'
			});
		});

		this.addHandler(PAPER_EVENTS.ELEMENT_MOUSEENTER, (event) => {
			this.eventBus.emit(PORT_EVENTS.SHOW, { element: event.element });
		});

		this.addHandler(PAPER_EVENTS.ELEMENT_MOUSELEAVE, (event) => {
			const selectedElements = this.stateStore.get('selection.elements');
			if (!selectedElements.includes(event.element)) {
				this.eventBus.emit(PORT_EVENTS.HIDE, { element: event.element });
			}
		});

		this.addHandler(PAPER_EVENTS.LINK_CONNECT, (event) => {
			this.handleLinkConnection(event);
		});

		this.addHandler(PAPER_EVENTS.LINK_DISCONNECT, (event) => {
			this.handleLinkDisconnection(event);
		});
	}

	/**
	 * Sets up selection management event handlers
	 */
	setupSelectionHandlers() {
		this.addHandler(SELECTION_EVENTS.ELEMENT_SELECTED, (event) => {
			const element = event.element;
			
			if (element.isElement()) {
				this.eventBus.emit(PORT_EVENTS.SHOW, { element });
			} else if (element.isLink()) {
				this.showConnectedElementPorts(element);
			}
		});

		this.addHandler(SELECTION_EVENTS.ELEMENT_UNSELECTED, (event) => {
			const element = event.element;
			
			if (element.isElement()) {
				this.eventBus.emit(PORT_EVENTS.HIDE, { element });
			} else if (element.isLink()) {
				this.hideConnectedElementPorts(element);
			}
		});

		this.addHandler(SELECTION_EVENTS.CLEARED, () => {
			this.hideAllPorts();
		});

		this.addHandler(SELECTION_EVENTS.DELETE_REQUESTED, () => {
			this.handleSelectionDeletion();
		});
	}

	/**
	 * Sets up port management event handlers
	 */
	setupPortHandlers() {
		this.addHandler(PORT_EVENTS.PORT_OCCUPIED, (event) => {
			this.stateStore.set('graph.metadata.modified', Date.now());
		});

		this.addHandler(PORT_EVENTS.PORT_FREED, (event) => {
			this.stateStore.set('graph.metadata.modified', Date.now());
		});

		this.addHandler(PORT_EVENTS.ELEMENT_PORTS_SHOWN, (event) => {
			const visiblePorts = this.stateStore.get('ports.visible');
			visiblePorts.add(event.element.id);
			this.stateStore.set('ports.visible', visiblePorts);
		});

		this.addHandler(PORT_EVENTS.ELEMENT_PORTS_HIDDEN, (event) => {
			const visiblePorts = this.stateStore.get('ports.visible');
			visiblePorts.delete(event.element.id);
			this.stateStore.set('ports.visible', visiblePorts);
		});
	}

	/**
	 * Sets up command execution event handlers
	 */
	setupCommandHandlers() {
		this.addHandler(COMMAND_EVENTS.EXECUTED, (event) => {
			this.stateStore.setBatch({
				'plugins.history.canUndo': event.canUndo,
				'plugins.history.canRedo': event.canRedo
			});
		});

		this.addHandler(COMMAND_EVENTS.UNDONE, (event) => {
			this.stateStore.setBatch({
				'plugins.history.canUndo': event.canUndo,
				'plugins.history.canRedo': event.canRedo
			});
		});

		this.addHandler(COMMAND_EVENTS.REDONE, (event) => {
			this.stateStore.setBatch({
				'plugins.history.canUndo': event.canUndo,
				'plugins.history.canRedo': event.canRedo
			});
		});

		this.addHandler(COMMAND_EVENTS.FAILED, (event) => {
			this.stateStore.set('app.error', `Command failed: ${event.command.getDescription()}`);
		});
	}

	/**
	 * Sets up validation event handlers
	 */
	setupValidationHandlers() {
		this.addHandler(VALIDATION_EVENTS.CONNECTION_VALIDATED, (event) => {
			if (!event.result.valid) {
				const errors = event.result.errors.map(e => e.reason).join(', ');
				this.stateStore.set('app.error', `Connection validation failed: ${errors}`);
			}
		});

		this.addHandler(VALIDATION_EVENTS.ELEMENT_MOVE_VALIDATED, (event) => {
			if (!event.result.valid) {
				const errors = event.result.errors.map(e => e.reason).join(', ');
				this.stateStore.set('app.error', `Move validation failed: ${errors}`);
			}
		});
	}

	/**
	 * Sets up UI interaction event handlers
	 */
	setupUIHandlers() {
		this.addHandler(UI_EVENTS.CONTEXT_MENU_REQUESTED, (event) => {
			this.stateStore.setBatch({
				'ui.contextMenu.visible': true,
				'ui.contextMenu.x': event.x,
				'ui.contextMenu.y': event.y,
				'ui.contextMenu.target': event.target,
				'ui.contextMenu.type': event.type
			});
		});

		this.addHandler(UI_EVENTS.TOOLBAR_TOOL_ACTIVATED, (event) => {
			const activeTools = this.stateStore.get('ui.toolbar.activeTools');
			activeTools.add(event.tool);
			this.stateStore.set('ui.toolbar.activeTools', activeTools);
			
			if (event.tool === 'add') {
				this.stateStore.set('app.mode', 'add');
			}
		});

		this.addHandler(UI_EVENTS.TOOLBAR_TOOL_DEACTIVATED, (event) => {
			const activeTools = this.stateStore.get('ui.toolbar.activeTools');
			activeTools.delete(event.tool);
			this.stateStore.set('ui.toolbar.activeTools', activeTools);
			
			if (event.tool === 'add') {
				this.stateStore.set('app.mode', 'select');
			}
		});
	}

	/**
	 * Sets up keyboard event handlers
	 */
	setupKeyboardHandlers() {
		this.addHandler(KEYBOARD_EVENTS.GLOBAL_KEYDOWN, (event) => {
			if (event.key === 'Escape') {
				this.handleEscapeKey();
			} else if (event.ctrlKey && event.key === 'a') {
				event.originalEvent.preventDefault();
				this.eventBus.emit(SELECTION_EVENTS.SELECT_ALL);
			} else if (event.ctrlKey && event.key === 'd') {
				event.originalEvent.preventDefault();
				this.handleDuplication();
			}
		});
	}

	/**
	 * Sets up state change event handlers
	 */
	setupStateHandlers() {
		this.addHandler(STATE_EVENTS.CHANGED, (event) => {
			if (event.path === 'app.error' && event.newValue) {
				setTimeout(() => {
					this.stateStore.set('app.error', null);
				}, 5000);
			}
		});
	}

	/**
	 * Handles element creation from blank area click
	 */
	handleElementCreation(coordinates) {
		const graphService = this.container.get('graphService');
		const validationService = this.container.get('validationService');
		
		const AddElementCommand = this.container.get('AddElementCommand');
		const command = new AddElementCommand(graphService, validationService, {
			position: coordinates,
			elementType: 'rectangle'
		});

		this.commandManager.execute(command);
	}

	/**
	 * Handles link connection event
	 */
	handleLinkConnection(event) {
		const portService = this.container.get('portService');
		const connectionMode = this.stateStore.get('connections.mode');
		
		if (connectionMode > 1) {
			const connectionManager = this.container.get('connectionManager');
			connectionManager.replaceWithMultipleLines(
				event.link,
				portService,
				this.container.get('graphService').graph
			);
		}
		
		portService.handleLinkConnect(event.link);
	}

	/**
	 * Handles link disconnection event
	 */
	handleLinkDisconnection(event) {
		const portService = this.container.get('portService');
		portService.handleLinkDisconnect(event.link);
	}

	/**
	 * Shows ports for elements connected to a link
	 */
	showConnectedElementPorts(link) {
		const sourceElement = link.getSourceElement();
		const targetElement = link.getTargetElement();

		if (sourceElement) {
			this.eventBus.emit(PORT_EVENTS.SHOW, { element: sourceElement });
		}

		if (targetElement && targetElement !== sourceElement) {
			this.eventBus.emit(PORT_EVENTS.SHOW, { element: targetElement });
		}
	}

	/**
	 * Hides ports for elements connected to a link
	 */
	hideConnectedElementPorts(link) {
		const selectedElements = this.stateStore.get('selection.elements');
		const sourceElement = link.getSourceElement();
		const targetElement = link.getTargetElement();

		if (sourceElement && !selectedElements.includes(sourceElement)) {
			this.eventBus.emit(PORT_EVENTS.HIDE, { element: sourceElement });
		}

		if (targetElement && 
			targetElement !== sourceElement && 
			!selectedElements.includes(targetElement)) {
			this.eventBus.emit(PORT_EVENTS.HIDE, { element: targetElement });
		}
	}

	/**
	 * Hides all visible ports
	 */
	hideAllPorts() {
		const visiblePorts = this.stateStore.get('ports.visible');
		const graphService = this.container.get('graphService');
		
		for (const elementId of visiblePorts) {
			const element = graphService.getElementById(elementId);
			if (element) {
				this.eventBus.emit(PORT_EVENTS.HIDE, { element });
			}
		}
	}

	/**
	 * Handles selection deletion
	 */
	handleSelectionDeletion() {
		const selectedElements = this.stateStore.get('selection.elements');
		
		if (selectedElements.length === 0) return;

		const graphService = this.container.get('graphService');
		const portService = this.container.get('portService');
		
		const DeleteElementCommand = this.container.get('DeleteElementCommand');
		const command = new DeleteElementCommand(graphService, portService, selectedElements);

		this.commandManager.execute(command);
	}

	/**
	 * Handles escape key press
	 */
	handleEscapeKey() {
		this.stateStore.setBatch({
			'app.mode': 'select',
			'ui.contextMenu.visible': false,
			'connections.creating': false
		});

		this.eventBus.emit(SELECTION_EVENTS.CLEAR);
	}

	/**
	 * Handles element duplication
	 */
	handleDuplication() {
		const selectedElements = this.stateStore.get('selection.elements')
			.filter(el => el.isElement());
		
		if (selectedElements.length === 0) return;

		const graphService = this.container.get('graphService');
		const offset = { x: 20, y: 20 };
		const duplicatedElements = [];

		selectedElements.forEach(element => {
			const cloned = graphService.cloneElement(element, offset);
			duplicatedElements.push(cloned);
		});

		this.eventBus.emit(SELECTION_EVENTS.SELECT_MULTIPLE, {
			elements: duplicatedElements
		});
	}

	/**
	 * Adds an event handler
	 */
	addHandler(eventType, handler, options = {}) {
		const { priority = 0, once = false } = options;
		
		const handlerInfo = {
			handler,
			priority,
			once,
			eventType
		};

		if (!this.handlers.has(eventType)) {
			this.handlers.set(eventType, []);
		}

		this.handlers.get(eventType).push(handlerInfo);
		
		if (once) {
			this.eventBus.once(eventType, handler, { priority });
		} else {
			this.eventBus.on(eventType, handler, { priority });
		}
	}

	/**
	 * Removes an event handler
	 */
	removeHandler(eventType, handler) {
		if (this.handlers.has(eventType)) {
			const handlers = this.handlers.get(eventType);
			const index = handlers.findIndex(h => h.handler === handler);
			
			if (index > -1) {
				handlers.splice(index, 1);
				if (handlers.length === 0) {
					this.handlers.delete(eventType);
				}
			}
		}

		this.eventBus.off(eventType, handler);
	}

	/**
	 * Removes all handlers for an event type
	 */
	removeAllHandlers(eventType) {
		if (this.handlers.has(eventType)) {
			const handlers = this.handlers.get(eventType);
			handlers.forEach(handlerInfo => {
				this.eventBus.off(eventType, handlerInfo.handler);
			});
			this.handlers.delete(eventType);
		}
	}

	/**
	 * Gets handlers for an event type
	 */
	getHandlers(eventType) {
		return this.handlers.get(eventType) || [];
	}

	/**
	 * Gets all registered event types
	 */
	getRegisteredEventTypes() {
		return Array.from(this.handlers.keys());
	}

	/**
	 * Gets handler statistics
	 */
	getStats() {
		const stats = {
			totalEventTypes: this.handlers.size,
			totalHandlers: 0,
			handlersByType: {}
		};

		for (const [eventType, handlers] of this.handlers) {
			stats.totalHandlers += handlers.length;
			stats.handlersByType[eventType] = handlers.length;
		}

		return stats;
	}

	/**
	 * Enables debug mode
	 */
	setDebugMode(enabled) {
		this.debugMode = enabled;
		
		if (enabled) {
			this.addHandler('*', (event) => {
				console.log('Event triggered:', event.type, event);
			});
		}
	}

	/**
	 * Performs cleanup when destroying
	 */
	cleanup() {
		for (const [eventType, handlers] of this.handlers) {
			handlers.forEach(handlerInfo => {
				this.eventBus.off(eventType, handlerInfo.handler);
			});
		}
		
		this.handlers.clear();
		this.initialized = false;
	}

	/**
	 * Destroys the event handlers
	 */
	destroy() {
		this.cleanup();
		this.eventBus.emit(EDITOR_EVENTS.DESTROYED);
	}
}