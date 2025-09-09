import { UI_EVENTS, KEYBOARD_EVENTS } from '../Events/EventTypes.js';

/**
 * Toolbar - Dynamic toolbar system for editor tools and actions
 */
export class Toolbar {
	constructor(eventBus, stateStore) {
		this.eventBus = eventBus;
		this.stateStore = stateStore;
		this.toolbarElement = null;
		this.initialized = false;
		this.tools = new Map();
		this.toolGroups = new Map();
		this.activeTools = new Set();
		this.shortcuts = new Map();
		
		this.bindEventHandlers();
	}

	/**
	 * Initializes the toolbar system
	 */
	init() {
		if (this.initialized) {
			console.warn('Toolbar: Already initialized');
			return;
		}

		this.createToolbarElement();
		this.setupDefaultTools();
		this.setupKeyboardShortcuts();
		this.syncWithState();
		this.initialized = true;

		this.eventBus.emit(UI_EVENTS.TOOLBAR_INITIALIZED);
	}

	/**
	 * Creates the main toolbar DOM element
	 */
	createToolbarElement() {
		this.toolbarElement = document.createElement('div');
		this.toolbarElement.id = 'editor-toolbar';
		this.toolbarElement.className = 'editor-toolbar';
		
		this.toolbarElement.style.cssText = `
			display: flex;
			align-items: center;
			gap: 8px;
			padding: 8px 12px;
			background: #f8f9fa;
			border-bottom: 1px solid #e9ecef;
			font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
			font-size: 14px;
			user-select: none;
			position: relative;
			z-index: 100;
		`;

		const targetContainer = document.querySelector('#toolbar-container') || document.body;
		targetContainer.appendChild(this.toolbarElement);
		this.addToolbarStyles();
	}

	/**
	 * Adds CSS styles for toolbar elements
	 */
	addToolbarStyles() {
		const styleId = 'toolbar-styles';
		if (document.getElementById(styleId)) return;

		const style = document.createElement('style');
		style.id = styleId;
		style.textContent = `
			.toolbar-tool {
				display: flex;
				align-items: center;
				justify-content: center;
				min-width: 36px;
				height: 36px;
				padding: 6px 12px;
				border: 1px solid #dee2e6;
				border-radius: 4px;
				background: #ffffff;
				color: #495057;
				cursor: pointer;
				transition: all 0.15s ease;
				font-weight: 500;
				text-decoration: none;
				position: relative;
			}
			
			.toolbar-tool:hover {
				background: #e9ecef;
				border-color: #adb5bd;
			}
			
			.toolbar-tool:active {
				background: #dee2e6;
				transform: translateY(1px);
			}
			
			.toolbar-tool.active {
				background: #007bff;
				color: white;
				border-color: #0056b3;
			}
			
			.toolbar-tool.active:hover {
				background: #0056b3;
			}
			
			.toolbar-tool.disabled {
				background: #f8f9fa;
				color: #6c757d;
				cursor: not-allowed;
				opacity: 0.65;
			}
			
			.toolbar-tool.disabled:hover {
				background: #f8f9fa;
				border-color: #dee2e6;
				transform: none;
			}
			
			.toolbar-tool-icon {
				width: 16px;
				height: 16px;
				margin-right: 6px;
			}
			
			.toolbar-tool-text {
				font-size: 13px;
				line-height: 1;
			}
			
			.toolbar-separator {
				width: 1px;
				height: 24px;
				background: #dee2e6;
				margin: 0 4px;
			}
			
			.toolbar-group {
				display: flex;
				align-items: center;
				gap: 4px;
				padding: 0 4px;
			}
			
			.toolbar-group-label {
				font-size: 11px;
				color: #6c757d;
				text-transform: uppercase;
				letter-spacing: 0.5px;
				margin-right: 8px;
				font-weight: 600;
			}
			
			.toolbar-dropdown {
				position: relative;
			}
			
			.toolbar-dropdown-content {
				position: absolute;
				top: 100%;
				left: 0;
				background: white;
				border: 1px solid #dee2e6;
				border-radius: 4px;
				box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
				min-width: 160px;
				z-index: 1000;
				display: none;
			}
			
			.toolbar-dropdown.open .toolbar-dropdown-content {
				display: block;
			}
			
			.toolbar-dropdown-item {
				display: block;
				width: 100%;
				padding: 8px 12px;
				border: none;
				background: none;
				text-align: left;
				cursor: pointer;
				color: #495057;
				transition: background-color 0.15s ease;
			}
			
			.toolbar-dropdown-item:hover {
				background: #f8f9fa;
			}
			
			.toolbar-tooltip {
				position: absolute;
				bottom: 100%;
				left: 50%;
				transform: translateX(-50%);
				background: #333;
				color: white;
				padding: 4px 8px;
				border-radius: 3px;
				font-size: 12px;
				white-space: nowrap;
				margin-bottom: 5px;
				opacity: 0;
				pointer-events: none;
				transition: opacity 0.2s ease;
				z-index: 10000;
			}
			
			.toolbar-tooltip::after {
				content: '';
				position: absolute;
				top: 100%;
				left: 50%;
				transform: translateX(-50%);
				border: 4px solid transparent;
				border-top-color: #333;
			}
			
			.toolbar-tool:hover .toolbar-tooltip {
				opacity: 1;
			}
		`;
		
		document.head.appendChild(style);
	}

	/**
	 * Sets up default toolbar tools
	 */
	setupDefaultTools() {
		this.createToolGroup('selection', 'Selection');
		this.addTool('selection', {
			id: 'select',
			label: 'Select',
			icon: '👆',
			tooltip: 'Selection Tool (V)',
			shortcut: 'v',
			active: true,
			action: () => this.setMode('select')
		});

		this.addTool('selection', {
			id: 'pan',
			label: 'Pan',
			icon: '✋',
			tooltip: 'Pan Tool (H)',
			shortcut: 'h',
			action: () => this.setMode('pan')
		});

		this.createSeparator();

		this.createToolGroup('elements', 'Elements');
		this.addTool('elements', {
			id: 'add-rectangle',
			label: 'Rectangle',
			icon: '⬜',
			tooltip: 'Add Rectangle (R)',
			shortcut: 'r',
			action: () => this.setAddMode('rectangle')
		});

		this.addTool('elements', {
			id: 'add-circle',
			label: 'Circle',
			icon: '⭕',
			tooltip: 'Add Circle (C)',
			shortcut: 'c',
			action: () => this.setAddMode('circle')
		});

		this.addTool('elements', {
			id: 'add-ellipse',
			label: 'Ellipse',
			icon: '⬯',
			tooltip: 'Add Ellipse (E)',
			shortcut: 'e',
			action: () => this.setAddMode('ellipse')
		});

		this.createSeparator();

		this.createToolGroup('connections', 'Connections');
		this.addConnectionModeTools();

		this.createSeparator();

		this.createToolGroup('edit', 'Edit');
		this.addTool('edit', {
			id: 'undo',
			label: 'Undo',
			icon: '↶',
			tooltip: 'Undo (Ctrl+Z)',
			shortcut: 'ctrl+z',
			action: () => this.executeUndo()
		});

		this.addTool('edit', {
			id: 'redo',
			label: 'Redo',
			icon: '↷',
			tooltip: 'Redo (Ctrl+Y)',
			shortcut: 'ctrl+y',
			action: () => this.executeRedo()
		});

		this.createSeparator();

		this.createToolGroup('view', 'View');
		this.addTool('view', {
			id: 'zoom-fit',
			label: 'Fit',
			icon: '🔍',
			tooltip: 'Fit to Content',
			action: () => this.fitContent()
		});

		this.addTool('view', {
			id: 'zoom-reset',
			label: 'Reset',
			icon: '🎯',
			tooltip: 'Reset View',
			action: () => this.resetView()
		});
	}

	/**
	 * Creates connection mode tools
	 */
	addConnectionModeTools() {
		const connectionModes = [
			{ count: 1, label: '1x', tooltip: 'Single Connection' },
			{ count: 2, label: '2x', tooltip: 'Double Connection' },
			{ count: 3, label: '3x', tooltip: 'Triple Connection' },
			{ count: 4, label: '4x', tooltip: 'Quad Connection' }
		];

		connectionModes.forEach(mode => {
			this.addTool('connections', {
				id: `connection-${mode.count}`,
				label: mode.label,
				tooltip: mode.tooltip,
				toggle: true,
				active: mode.count === 1,
				action: () => this.setConnectionMode(mode.count)
			});
		});
	}

	/**
	 * Creates a tool group
	 */
	createToolGroup(id, label) {
		const groupElement = document.createElement('div');
		groupElement.className = 'toolbar-group';
		groupElement.dataset.group = id;

		if (label) {
			const labelElement = document.createElement('span');
			labelElement.className = 'toolbar-group-label';
			labelElement.textContent = label;
			groupElement.appendChild(labelElement);
		}

		this.toolbarElement.appendChild(groupElement);
		this.toolGroups.set(id, groupElement);
	}

	/**
	 * Creates a separator between tool groups
	 */
	createSeparator() {
		const separator = document.createElement('div');
		separator.className = 'toolbar-separator';
		this.toolbarElement.appendChild(separator);
	}

	/**
	 * Adds a tool to a specific group
	 */
	addTool(groupId, toolConfig) {
		const group = this.toolGroups.get(groupId);
		if (!group) {
			console.warn(`Toolbar: Group '${groupId}' not found`);
			return null;
		}

		const toolElement = this.createToolElement(toolConfig);
		group.appendChild(toolElement);
		
		this.tools.set(toolConfig.id, {
			element: toolElement,
			config: toolConfig,
			group: groupId
		});

		if (toolConfig.shortcut) {
			this.shortcuts.set(toolConfig.shortcut.toLowerCase(), toolConfig.id);
		}

		if (toolConfig.active) {
			this.activateTool(toolConfig.id);
		}

		return toolElement;
	}

	/**
	 * Creates a tool element
	 */
	createToolElement(config) {
		const toolElement = document.createElement('button');
		toolElement.className = 'toolbar-tool';
		toolElement.dataset.tool = config.id;
		toolElement.type = 'button';

		if (config.icon) {
			const iconElement = document.createElement('span');
			iconElement.className = 'toolbar-tool-icon';
			iconElement.textContent = config.icon;
			toolElement.appendChild(iconElement);
		}

		if (config.label) {
			const textElement = document.createElement('span');
			textElement.className = 'toolbar-tool-text';
			textElement.textContent = config.label;
			toolElement.appendChild(textElement);
		}

		if (config.tooltip) {
			const tooltipElement = document.createElement('div');
			tooltipElement.className = 'toolbar-tooltip';
			tooltipElement.textContent = config.tooltip;
			toolElement.appendChild(tooltipElement);
		}

		toolElement.addEventListener('click', (event) => {
			event.preventDefault();
			this.handleToolClick(config.id);
		});

		return toolElement;
	}

	/**
	 * Handles tool click events
	 */
	handleToolClick(toolId) {
		const tool = this.tools.get(toolId);
		if (!tool || tool.config.disabled) return;

		this.eventBus.emit(UI_EVENTS.TOOLBAR_BUTTON_CLICKED, {
			toolId,
			tool: tool.config
		});

		if (tool.config.toggle) {
			this.toggleTool(toolId);
		} else {
			this.activateTool(toolId);
		}

		if (tool.config.action) {
			try {
				tool.config.action();
			} catch (error) {
				console.error('Toolbar: Error executing tool action:', error);
			}
		}
	}

	/**
	 * Activates a tool
	 */
	activateTool(toolId) {
		const tool = this.tools.get(toolId);
		if (!tool) return;

		if (!tool.config.toggle) {
			this.deactivateGroup(tool.group);
		}

		tool.element.classList.add('active');
		this.activeTools.add(toolId);

		this.stateStore.set('ui.toolbar.activeTools', this.activeTools);
		this.eventBus.emit(UI_EVENTS.TOOLBAR_TOOL_ACTIVATED, {
			toolId,
			tool: tool.config
		});
	}

	/**
	 * Deactivates a tool
	 */
	deactivateTool(toolId) {
		const tool = this.tools.get(toolId);
		if (!tool) return;

		tool.element.classList.remove('active');
		this.activeTools.delete(toolId);

		this.stateStore.set('ui.toolbar.activeTools', this.activeTools);
		this.eventBus.emit(UI_EVENTS.TOOLBAR_TOOL_DEACTIVATED, {
			toolId,
			tool: tool.config
		});
	}

	/**
	 * Toggles a tool's active state
	 */
	toggleTool(toolId) {
		if (this.activeTools.has(toolId)) {
			this.deactivateTool(toolId);
		} else {
			this.activateTool(toolId);
		}
	}

	/**
	 * Deactivates all tools in a group
	 */
	deactivateGroup(groupId) {
		for (const [toolId, tool] of this.tools) {
			if (tool.group === groupId && !tool.config.toggle) {
				this.deactivateTool(toolId);
			}
		}
	}

	/**
	 * Binds event handlers for toolbar interactions
	 */
	bindEventHandlers() {
		this.eventBus.on(KEYBOARD_EVENTS.GLOBAL_KEYDOWN, (event) => {
			this.handleKeyboardShortcut(event);
		});

		this.eventBus.on('state:plugins.history:changed', () => {
			this.updateHistoryTools();
		});

		this.eventBus.on('state:app.mode:changed', (event) => {
			this.syncModeWithTools(event.newValue);
		});
	}

	/**
	 * Sets up keyboard shortcuts
	 */
	setupKeyboardShortcuts() {
		document.addEventListener('keydown', (event) => {
			if (event.target.tagName === 'INPUT' || event.target.tagName === 'TEXTAREA') {
				return;
			}

			const shortcut = this.buildShortcutString(event);
			const toolId = this.shortcuts.get(shortcut);
			
			if (toolId) {
				event.preventDefault();
				this.handleToolClick(toolId);
			}
		});
	}

	/**
	 * Builds shortcut string from keyboard event
	 */
	buildShortcutString(event) {
		const parts = [];
		
		if (event.ctrlKey || event.metaKey) parts.push('ctrl');
		if (event.shiftKey) parts.push('shift');
		if (event.altKey) parts.push('alt');
		
		parts.push(event.key.toLowerCase());
		
		return parts.join('+');
	}

	/**
	 * Handles keyboard shortcut events
	 */
	handleKeyboardShortcut(event) {
		const shortcut = this.buildShortcutString(event.originalEvent);
		const toolId = this.shortcuts.get(shortcut);
		
		if (toolId && !event.originalEvent.defaultPrevented) {
			event.originalEvent.preventDefault();
			this.handleToolClick(toolId);
		}
	}

	/**
	 * Updates history tools based on command manager state
	 */
	updateHistoryTools() {
		const historyState = this.stateStore.get('plugins.history');
		
		this.setToolEnabled('undo', historyState.canUndo);
		this.setToolEnabled('redo', historyState.canRedo);
	}

	/**
	 * Synchronizes mode with tool states
	 */
	syncModeWithTools(mode) {
		this.deactivateGroup('selection');
		this.deactivateGroup('elements');

		switch (mode) {
			case 'select':
				this.activateTool('select');
				break;
			case 'pan':
				this.activateTool('pan');
				break;
			case 'add':
				const elementType = this.stateStore.get('app.elementType') || 'rectangle';
				this.activateTool(`add-${elementType}`);
				break;
		}
	}

	/**
	 * Sets tool enabled state
	 */
	setToolEnabled(toolId, enabled) {
		const tool = this.tools.get(toolId);
		if (!tool) return;

		tool.config.disabled = !enabled;
		tool.element.classList.toggle('disabled', !enabled);
	}

	/**
	 * Synchronizes toolbar with current state
	 */
	syncWithState() {
		const currentMode = this.stateStore.get('app.mode');
		this.syncModeWithTools(currentMode);
		this.updateHistoryTools();
	}

	/**
	 * Tool action: Set editor mode
	 */
	setMode(mode) {
		this.stateStore.set('app.mode', mode);
	}

	/**
	 * Tool action: Set add mode with element type
	 */
	setAddMode(elementType) {
		this.stateStore.setBatch({
			'app.mode': 'add',
			'app.elementType': elementType
		});
	}

	/**
	 * Tool action: Set connection mode
	 */
	setConnectionMode(count) {
		this.stateStore.set('connections.mode', count);
		
		for (let i = 1; i <= 4; i++) {
			const toolId = `connection-${i}`;
			if (i === count) {
				this.activateTool(toolId);
			} else {
				this.deactivateTool(toolId);
			}
		}
	}

	/**
	 * Tool action: Execute undo
	 */
	executeUndo() {
		this.eventBus.emit('command:undo-requested');
	}

	/**
	 * Tool action: Execute redo
	 */
	executeRedo() {
		this.eventBus.emit('command:redo-requested');
	}

	/**
	 * Tool action: Fit content to view
	 */
	fitContent() {
		this.eventBus.emit('canvas:fit-content');
	}

	/**
	 * Tool action: Reset view
	 */
	resetView() {
		this.eventBus.emit('canvas:reset-view');
	}

	/**
	 * Adds a custom tool to existing group
	 */
	addCustomTool(groupId, toolConfig) {
		return this.addTool(groupId, toolConfig);
	}

	/**
	 * Removes a tool
	 */
	removeTool(toolId) {
		const tool = this.tools.get(toolId);
		if (!tool) return false;

		if (tool.element.parentNode) {
			tool.element.parentNode.removeChild(tool.element);
		}

		this.tools.delete(toolId);
		this.activeTools.delete(toolId);

		if (tool.config.shortcut) {
			this.shortcuts.delete(tool.config.shortcut.toLowerCase());
		}

		return true;
	}

	/**
	 * Gets toolbar statistics
	 */
	getStats() {
		return {
			toolCount: this.tools.size,
			groupCount: this.toolGroups.size,
			activeToolCount: this.activeTools.size,
			shortcutCount: this.shortcuts.size,
			tools: Array.from(this.tools.keys()),
			groups: Array.from(this.toolGroups.keys()),
			activeTools: Array.from(this.activeTools)
		};
	}

	/**
	 * Destroys the toolbar
	 */
	destroy() {
		if (this.toolbarElement && this.toolbarElement.parentNode) {
			this.toolbarElement.parentNode.removeChild(this.toolbarElement);
		}

		const styleElement = document.getElementById('toolbar-styles');
		if (styleElement && styleElement.parentNode) {
			styleElement.parentNode.removeChild(styleElement);
		}

		this.tools.clear();
		this.toolGroups.clear();
		this.activeTools.clear();
		this.shortcuts.clear();
		this.initialized = false;

		this.eventBus.emit(UI_EVENTS.TOOLBAR_DESTROYED);
	}
}