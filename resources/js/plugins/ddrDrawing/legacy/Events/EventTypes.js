/**
 * EventTypes - Centralized constants for all system events
 * Provides type safety and prevents event name typos across the application
 */

// =====================================
// EDITOR LIFECYCLE EVENTS
// =====================================
export const EDITOR_EVENTS = {
	INITIALIZING: 'editor:initializing',
	INITIALIZED: 'editor:initialized',
	INITIALIZATION_FAILED: 'editor:initialization-failed',
	READY: 'editor:ready',
	DESTROYING: 'editor:destroying',
	DESTROYED: 'editor:destroyed',
	BEFORE_UNLOAD: 'editor:before-unload',
	REQUEST_DESTROY: 'editor:request-destroy',
	TOGGLE_DEBUG: 'editor:toggle-debug'
};

// =====================================
// GRAPH SERVICE EVENTS
// =====================================
export const GRAPH_EVENTS = {
	INITIALIZED: 'graph:initialized',
	SERVICE_DESTROYED: 'graph:service-destroyed',
	CLEARED: 'graph:cleared',
	LOADED: 'graph:loaded',
	LOAD_FAILED: 'graph:load-failed',
	EXPORTED: 'graph:exported',
	VALIDATED: 'graph:validated',
	
	// Graph manipulation
	CLEAR: 'graph:clear',
	LOAD: 'graph:load',
	EXPORT: 'graph:export'
};

// =====================================
// ELEMENT EVENTS
// =====================================
export const ELEMENT_EVENTS = {
	ADDED: 'element:added',
	REMOVED: 'element:removed',
	MOVED: 'element:moved',
	RESIZED: 'element:resized',
	CREATED: 'element:created',
	
	// Element operations
	CREATE: 'element:create',
	
	// Validation events
	VALIDATE_MOVE: 'element:validate-move',
	VALIDATE_RESIZE: 'element:validate-resize'
};

// =====================================
// LINK EVENTS
// =====================================
export const LINK_EVENTS = {
	ADDED: 'link:added',
	REMOVED: 'link:removed',
	CONNECTED: 'link:connected',
	DISCONNECTED: 'link:disconnected',
	
	// Link operations
	CREATE: 'link:create',
	CONNECT: 'link:connect',
	DISCONNECT: 'link:disconnect'
};

// =====================================
// CELL EVENTS (ELEMENTS AND LINKS)
// =====================================
export const CELL_EVENTS = {
	STYLE_CHANGED: 'cell:style-changed',
	HIGHLIGHT: 'cell:highlight',
	UNHIGHLIGHT: 'cell:unhighlight'
};

// =====================================
// PAPER SERVICE EVENTS
// =====================================
export const PAPER_EVENTS = {
	INITIALIZED: 'paper:initialized',
	SERVICE_DESTROYED: 'paper:service-destroyed',
	RESIZED: 'paper:resized',
	ZOOMED: 'paper:zoomed',
	PANNED: 'paper:panned',
	CONTENT_FITTED: 'paper:content-fitted',
	VIEW_RESET: 'paper:view-reset',
	INTERACTIVE_CHANGED: 'paper:interactive-changed',
	
	// Blank area interactions
	BLANK_POINTERDOWN: 'paper:blank-pointerdown',
	BLANK_POINTERUP: 'paper:blank-pointerup',
	BLANK_POINTERMOVE: 'paper:blank-pointermove',
	BLANK_CLICK: 'paper:blank-click',
	BLANK_CONTEXTMENU: 'paper:blank-contextmenu',
	
	// Element interactions
	ELEMENT_POINTERDOWN: 'paper:element-pointerdown',
	ELEMENT_POINTERUP: 'paper:element-pointerup',
	ELEMENT_POINTERMOVE: 'paper:element-pointermove',
	ELEMENT_CLICK: 'paper:element-click',
	ELEMENT_CONTEXTMENU: 'paper:element-contextmenu',
	ELEMENT_MOUSEENTER: 'paper:element-mouseenter',
	ELEMENT_MOUSELEAVE: 'paper:element-mouseleave',
	
	// Link interactions
	LINK_POINTERDOWN: 'paper:link-pointerdown',
	LINK_POINTERUP: 'paper:link-pointerup',
	LINK_POINTERMOVE: 'paper:link-pointermove',
	LINK_CLICK: 'paper:link-click',
	LINK_CONTEXTMENU: 'paper:link-contextmenu',
	LINK_CONNECT: 'paper:link-connect',
	LINK_DISCONNECT: 'paper:link-disconnect',
	
	// Cell interactions
	CELL_HIGHLIGHT: 'paper:cell-highlight',
	CELL_UNHIGHLIGHT: 'paper:cell-unhighlight',
	
	// Paper configuration
	SET_INTERACTIVE: 'paper:set-interactive'
};

// =====================================
// CANVAS EVENTS
// =====================================
export const CANVAS_EVENTS = {
	RESIZE: 'canvas:resize',
	ZOOM: 'canvas:zoom',
	PAN: 'canvas:pan',
	FIT_CONTENT: 'canvas:fit-content',
	RESET_VIEW: 'canvas:reset-view'
};

// =====================================
// PORT SERVICE EVENTS
// =====================================
export const PORT_EVENTS = {
	SERVICE_INITIALIZED: 'ports:service-initialized',
	SERVICE_DESTROYED: 'ports:service-destroyed',
	
	// Element port management
	ELEMENT_INITIALIZED: 'ports:element-initialized',
	ELEMENT_CLEANED: 'ports:element-cleaned',
	ELEMENT_PORTS_SHOWN: 'ports:element-ports-shown',
	ELEMENT_PORTS_HIDDEN: 'ports:element-ports-hidden',
	
	// Port operations
	PORT_ADDED: 'ports:port-added',
	PORT_REMOVED: 'ports:port-removed',
	ALL_PORTS_REMOVED: 'ports:all-ports-removed',
	PORT_OCCUPIED: 'ports:port-occupied',
	PORT_FREED: 'ports:port-freed',
	
	// Link connections
	LINK_CONNECTED: 'ports:link-connected',
	LINK_DISCONNECTED: 'ports:link-disconnected',
	
	// State synchronization
	ALL_STATES_SYNCED: 'ports:all-states-synced',
	
	// Port operations (commands)
	SHOW: 'ports:show',
	HIDE: 'ports:hide',
	ADD: 'ports:add',
	REMOVE: 'ports:remove'
};

// =====================================
// SELECTION SERVICE EVENTS
// =====================================
export const SELECTION_EVENTS = {
	SERVICE_INITIALIZED: 'selection:service-initialized',
	SERVICE_DESTROYED: 'selection:service-destroyed',
	
	// Element selection
	ELEMENT_SELECTED: 'selection:element-selected',
	ELEMENT_UNSELECTED: 'selection:element-unselected',
	MULTIPLE_SELECTED: 'selection:multiple-selected',
	ALL_SELECTED: 'selection:all-selected',
	INVERTED: 'selection:inverted',
	CLEARED: 'selection:cleared',
	DELETED: 'selection:deleted',
	GROUPED: 'selection:grouped',
	BY_CRITERIA: 'selection:by-criteria',
	
	// Area selection
	AREA_STARTED: 'selection:area-started',
	AREA_UPDATED: 'selection:area-updated',
	AREA_COMPLETED: 'selection:area-completed',
	AREA_CANCELLED: 'selection:area-cancelled',
	AREA_SELECTED: 'selection:area-selected',
	
	// Selection commands
	SELECT_ELEMENT: 'selection:select-element',
	SELECT_MULTIPLE: 'selection:select-multiple',
	CLEAR: 'selection:clear',
	DELETE_REQUESTED: 'selection:delete-requested',
	INVERT: 'selection:invert',
	SELECT_ALL: 'selection:select-all'
};

// =====================================
// VALIDATION SERVICE EVENTS
// =====================================
export const VALIDATION_EVENTS = {
	SERVICE_INITIALIZED: 'validation:service-initialized',
	SERVICE_DESTROYED: 'validation:service-destroyed',
	
	// Validation results
	CONNECTION_VALIDATED: 'validation:connection-validated',
	ELEMENT_MOVE_VALIDATED: 'validation:element-move-validated',
	ELEMENT_RESIZE_VALIDATED: 'validation:element-resize-validated',
	ELEMENT_CREATION_VALIDATED: 'validation:element-creation-validated',
	GRAPH_VALIDATED: 'validation:graph-validated',
	GRAPH_DATA_VALIDATED: 'validation:graph-data-validated',
	
	// Rule management
	RULE_ADDED: 'validation:rule-added',
	RULE_REMOVED: 'validation:rule-removed',
	RULE_TOGGLED: 'validation:rule-toggled',
	CUSTOM_VALIDATOR_ADDED: 'validation:custom-validator-added',
	CUSTOM_VALIDATOR_REMOVED: 'validation:custom-validator-removed',
	
	// Validation requests
	VALIDATE: 'validation:validate'
};

// =====================================
// CONNECTION EVENTS
// =====================================
export const CONNECTION_EVENTS = {
	VALIDATE: 'connection:validate',
	CREATED: 'connection:created',
	REMOVED: 'connection:removed'
};

// =====================================
// MAGNET EVENTS
// =====================================
export const MAGNET_EVENTS = {
	VALIDATE: 'magnet:validate'
};

// =====================================
// COMMAND MANAGER EVENTS
// =====================================
export const COMMAND_EVENTS = {
	EXECUTED: 'command:executed',
	UNDONE: 'command:undone',
	REDONE: 'command:redone',
	FAILED: 'command:failed',
	VALIDATION_FAILED: 'command:validation-failed',
	EXECUTION_FAILED: 'command:execution-failed',
	EXECUTION_ERROR: 'command:execution-error',
	UNDO_FAILED: 'command:undo-failed',
	UNDO_ERROR: 'command:undo-error',
	REDO_FAILED: 'command:redo-failed',
	REDO_ERROR: 'command:redo-error',
	
	// Batch operations
	BATCH_COMPLETED: 'command:batch-completed',
	BATCH_CANCELLED: 'command:batch-cancelled',
	
	// History management
	HISTORY_CLEARED: 'command:history-cleared',
	MANAGER_DESTROYED: 'command:manager-destroyed'
};

// =====================================
// STATE STORE EVENTS
// =====================================
export const STATE_EVENTS = {
	CHANGED: 'state:changed',
	BATCH_CHANGED: 'state:batch-changed',
	RESTORED: 'state:restored',
	RESET: 'state:reset',
	DESTROYED: 'state:destroyed'
};

// =====================================
// KEYBOARD EVENTS
// =====================================
export const KEYBOARD_EVENTS = {
	GLOBAL_KEYDOWN: 'keyboard:global-keydown',
	KEYDOWN: 'keyboard:keydown'
};

// =====================================
// UI EVENTS
// =====================================
export const UI_EVENTS = {
	CONTEXT_MENU_REQUESTED: 'ui:context-menu-requested',
	CONTEXT_MENU_SHOWN: 'ui:context-menu-shown',
	CONTEXT_MENU_HIDDEN: 'ui:context-menu-hidden',
	CONTEXT_MENU_ITEM_CLICKED: 'ui:context-menu-item-clicked',
	
	TOOLBAR_BUTTON_CLICKED: 'ui:toolbar-button-clicked',
	TOOLBAR_TOOL_ACTIVATED: 'ui:toolbar-tool-activated',
	TOOLBAR_TOOL_DEACTIVATED: 'ui:toolbar-tool-deactivated',
	
	GUIDELINES_SHOWN: 'ui:guidelines-shown',
	GUIDELINES_HIDDEN: 'ui:guidelines-hidden',
	GUIDELINES_UPDATED: 'ui:guidelines-updated',
	
	PANEL_OPENED: 'ui:panel-opened',
	PANEL_CLOSED: 'ui:panel-closed',
	PANEL_RESIZED: 'ui:panel-resized'
};

// =====================================
// PLUGIN EVENTS
// =====================================
export const PLUGIN_EVENTS = {
	REGISTERED: 'plugin:registered',
	UNREGISTERED: 'plugin:unregistered',
	REGISTRATION_FAILED: 'plugin:registration-failed',
	INITIALIZED: 'plugin:initialized',
	DESTROYED: 'plugin:destroyed',
	
	// Callouts plugin
	CALLOUT_ADDED: 'plugin:callout-added',
	CALLOUT_REMOVED: 'plugin:callout-removed',
	CALLOUT_EDITED: 'plugin:callout-edited',
	CALLOUT_UPDATED: 'plugin:callout-updated',
	
	// Guidelines plugin
	GUIDELINE_CREATED: 'plugin:guideline-created',
	GUIDELINE_REMOVED: 'plugin:guideline-removed',
	SNAP_APPLIED: 'plugin:snap-applied',
	
	// Connection plugin
	CONNECTION_MODE_CHANGED: 'plugin:connection-mode-changed',
	MULTIPLE_CONNECTIONS_CREATED: 'plugin:multiple-connections-created'
};

// =====================================
// DEBUG EVENTS
// =====================================
export const DEBUG_EVENTS = {
	MODE_CHANGED: 'debug:mode-changed',
	LOG_MESSAGE: 'debug:log-message',
	PERFORMANCE_MEASURE: 'debug:performance-measure',
	MEMORY_USAGE: 'debug:memory-usage'
};

// =====================================
// ERROR EVENTS
// =====================================
export const ERROR_EVENTS = {
	VALIDATION_ERROR: 'error:validation',
	EXECUTION_ERROR: 'error:execution',
	NETWORK_ERROR: 'error:network',
	PARSING_ERROR: 'error:parsing',
	UNKNOWN_ERROR: 'error:unknown'
};

// =====================================
// PERFORMANCE EVENTS
// =====================================
export const PERFORMANCE_EVENTS = {
	MEASURE_START: 'performance:measure-start',
	MEASURE_END: 'performance:measure-end',
	BENCHMARK_COMPLETED: 'performance:benchmark-completed',
	MEMORY_USAGE_RECORDED: 'performance:memory-usage-recorded'
};

// =====================================
// UTILITY FUNCTIONS
// =====================================

/**
 * Gets all event types as a flat array
 */
export function getAllEventTypes() {
	const allEvents = [];
	
	const eventCategories = [
		EDITOR_EVENTS,
		GRAPH_EVENTS,
		ELEMENT_EVENTS,
		LINK_EVENTS,
		CELL_EVENTS,
		PAPER_EVENTS,
		CANVAS_EVENTS,
		PORT_EVENTS,
		SELECTION_EVENTS,
		VALIDATION_EVENTS,
		CONNECTION_EVENTS,
		MAGNET_EVENTS,
		COMMAND_EVENTS,
		STATE_EVENTS,
		KEYBOARD_EVENTS,
		UI_EVENTS,
		PLUGIN_EVENTS,
		DEBUG_EVENTS,
		ERROR_EVENTS,
		PERFORMANCE_EVENTS
	];
	
	eventCategories.forEach(category => {
		Object.values(category).forEach(event => {
			allEvents.push(event);
		});
	});
	
	return allEvents;
}

/**
 * Gets events by category
 */
export function getEventsByCategory(categoryName) {
	const categories = {
		editor: EDITOR_EVENTS,
		graph: GRAPH_EVENTS,
		element: ELEMENT_EVENTS,
		link: LINK_EVENTS,
		cell: CELL_EVENTS,
		paper: PAPER_EVENTS,
		canvas: CANVAS_EVENTS,
		port: PORT_EVENTS,
		selection: SELECTION_EVENTS,
		validation: VALIDATION_EVENTS,
		connection: CONNECTION_EVENTS,
		magnet: MAGNET_EVENTS,
		command: COMMAND_EVENTS,
		state: STATE_EVENTS,
		keyboard: KEYBOARD_EVENTS,
		ui: UI_EVENTS,
		plugin: PLUGIN_EVENTS,
		debug: DEBUG_EVENTS,
		error: ERROR_EVENTS,
		performance: PERFORMANCE_EVENTS
	};
	
	return categories[categoryName] || {};
}

/**
 * Validates if an event type exists
 */
export function isValidEventType(eventType) {
	return getAllEventTypes().includes(eventType);
}

/**
 * Gets event category from event type
 */
export function getEventCategory(eventType) {
	if (!eventType || typeof eventType !== 'string') {
		return null;
	}
	
	const colonIndex = eventType.indexOf(':');
	return colonIndex > 0 ? eventType.substring(0, colonIndex) : null;
}

/**
 * Creates a namespaced event type
 */
export function createEventType(category, action) {
	return `${category}:${action}`;
}

/**
 * Gets all event categories
 */
export function getEventCategories() {
	return [
		'editor',
		'graph',
		'element',
		'link',
		'cell',
		'paper',
		'canvas',
		'port',
		'selection',
		'validation',
		'connection',
		'magnet',
		'command',
		'state',
		'keyboard',
		'ui',
		'plugin',
		'debug',
		'error',
		'performance'
	];
}

/**
 * Filters events by pattern
 */
export function filterEventsByPattern(pattern) {
	const allEvents = getAllEventTypes();
	const regex = new RegExp(pattern.replace('*', '.*'), 'i');
	return allEvents.filter(event => regex.test(event));
}

// =====================================
// COMPOSITE EVENT GROUPS
// =====================================

/**
 * Events related to user interactions
 */
export const USER_INTERACTION_EVENTS = [
	...Object.values(PAPER_EVENTS).filter(event => 
		event.includes('click') || 
		event.includes('pointer') || 
		event.includes('mouse')
	),
	...Object.values(KEYBOARD_EVENTS),
	...Object.values(SELECTION_EVENTS)
];

/**
 * Events related to graph modifications
 */
export const GRAPH_MODIFICATION_EVENTS = [
	...Object.values(ELEMENT_EVENTS),
	...Object.values(LINK_EVENTS),
	...Object.values(GRAPH_EVENTS)
];

/**
 * Events related to command execution
 */
export const COMMAND_EXECUTION_EVENTS = [
	COMMAND_EVENTS.EXECUTED,
	COMMAND_EVENTS.UNDONE,
	COMMAND_EVENTS.REDONE,
	COMMAND_EVENTS.FAILED
];

/**
 * Events related to validation
 */
export const VALIDATION_RELATED_EVENTS = [
	...Object.values(VALIDATION_EVENTS),
	CONNECTION_EVENTS.VALIDATE,
	MAGNET_EVENTS.VALIDATE
];

/**
 * Events related to UI updates
 */
export const UI_UPDATE_EVENTS = [
	...Object.values(UI_EVENTS),
	...Object.values(SELECTION_EVENTS),
	...Object.values(PORT_EVENTS).filter(event => 
		event.includes('shown') || event.includes('hidden')
	)
];

// =====================================
// DEFAULT EXPORTS
// =====================================
export default {
	EDITOR_EVENTS,
	GRAPH_EVENTS,
	ELEMENT_EVENTS,
	LINK_EVENTS,
	CELL_EVENTS,
	PAPER_EVENTS,
	CANVAS_EVENTS,
	PORT_EVENTS,
	SELECTION_EVENTS,
	VALIDATION_EVENTS,
	CONNECTION_EVENTS,
	MAGNET_EVENTS,
	COMMAND_EVENTS,
	STATE_EVENTS,
	KEYBOARD_EVENTS,
	UI_EVENTS,
	PLUGIN_EVENTS,
	DEBUG_EVENTS,
	ERROR_EVENTS,
	PERFORMANCE_EVENTS,
	
	// Utility functions
	getAllEventTypes,
	getEventsByCategory,
	isValidEventType,
	getEventCategory,
	createEventType,
	getEventCategories,
	filterEventsByPattern,
	
	// Composite groups
	USER_INTERACTION_EVENTS,
	GRAPH_MODIFICATION_EVENTS,
	COMMAND_EXECUTION_EVENTS,
	VALIDATION_RELATED_EVENTS,
	UI_UPDATE_EVENTS
};