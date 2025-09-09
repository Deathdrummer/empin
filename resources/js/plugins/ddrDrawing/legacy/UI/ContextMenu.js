 
import { UI_EVENTS, PORT_EVENTS } from '../Events/EventTypes.js';

/**
 * ContextMenu - Dynamic context menu system for editor interactions
 */
export class ContextMenu {
	constructor(eventBus, stateStore) {
		this.eventBus = eventBus;
		this.stateStore = stateStore;
		this.menuElement = null;
		this.initialized = false;
		this.currentTarget = null;
		this.currentTargetType = null;
		this.isVisible = false;
		this.menuItems = new Map();
		this.customMenuProviders = new Map();
		
		this.bindEventHandlers();
	}

	/**
	 * Initializes the context menu system
	 */
	init() {
		if (this.initialized) {
			console.warn('ContextMenu: Already initialized');
			return;
		}

		this.createMenuElement();
		this.setupDefaultMenuItems();
		this.setupEventListeners();
		this.initialized = true;

		this.eventBus.emit(UI_EVENTS.CONTEXT_MENU_INITIALIZED);
	}

	/**
	 * Creates the main menu DOM element
	 */
	createMenuElement() {
		this.menuElement = document.createElement('div');
		this.menuElement.id = 'editor-context-menu';
		this.menuElement.className = 'context-menu';
		
		this.menuElement.style.cssText = `
			position: fixed;
			background: white;
			border: 1px solid #e0e0e0;
			border-radius: 6px;
			box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
			padding: 4px 0;
			min-width: 160px;
			max-width: 300px;
			z-index: 10000;
			display: none;
			font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
			font-size: 14px;
			line-height: 1.4;
			user-select: none;
		`;

		document.body.appendChild(this.menuElement);
		this.addMenuStyles();
	}

	/**
	 * Adds CSS styles for menu items and interactions
	 */
	addMenuStyles() {
		const styleId = 'context-menu-styles';
		if (document.getElementById(styleId)) return;

		const style = document.createElement('style');
		style.id = styleId;
		style.textContent = `
			.context-menu-item {
				padding: 8px 16px;
				cursor: pointer;
				color: #333;
				border: none;
				background: none;
				width: 100%;
				text-align: left;
				display: flex;
				align-items: center;
				transition: background-color 0.15s ease;
			}
			
			.context-menu-item:hover {
				background-color: #f5f5f5;
			}
			
			.context-menu-item:active {
				background-color: #e8e8e8;
			}
			
			.context-menu-item.disabled {
				color: #999;
				cursor: not-allowed;
			}
			
			.context-menu-item.disabled:hover {
				background-color: transparent;
			}
			
			.context-menu-separator {
				height: 1px;
				background-color: #e0e0e0;
				margin: 4px 0;
			}
			
			.context-menu-item-icon {
				width: 16px;
				height: 16px;
				margin-right: 8px;
				display: inline-block;
			}
			
			.context-menu-item-text {
				flex: 1;
			}
			
			.context-menu-item-shortcut {
				color: #999;
				font-size: 12px;
				margin-left: 16px;
			}
			
			.context-menu-submenu {
				position: relative;
			}
			
			.context-menu-submenu-indicator {
				margin-left: auto;
				color: #999;
			}
			
			.context-menu-submenu .context-menu {
				position: absolute;
				left: 100%;
				top: 0;
				display: none;
			}
			
			.context-menu-submenu:hover .context-menu {
				display: block;
			}
		`;
		
		document.head.appendChild(style);
	}

	/**
	 * Sets up default menu items for different target types
	 */
	setupDefaultMenuItems() {
		this.registerMenuProvider('element', (target) => [
			{
				id: 'add-callout',
				label: 'Add Callout',
				icon: '💬',
				action: () => this.addCallout(target)
			},
			{ type: 'separator' },
			{
				id: 'add-port-top',
				label: 'Add Port - Top',
				icon: '⬆️',
				action: () => this.addPort(target, 'top')
			},
			{
				id: 'add-port-right',
				label: 'Add Port - Right',
				icon: '➡️',
				action: () => this.addPort(target, 'right')
			},
			{
				id: 'add-port-bottom',
				label: 'Add Port - Bottom',
				icon: '⬇️',
				action: () => this.addPort(target, 'bottom')
			},
			{
				id: 'add-port-left',
				label: 'Add Port - Left',
				icon: '⬅️',
				action: () => this.addPort(target, 'left')
			},
			{ type: 'separator' },
			{
				id: 'duplicate',
				label: 'Duplicate',
				icon: '📋',
				shortcut: 'Ctrl+D',
				action: () => this.duplicateElement(target)
			},
			{
				id: 'delete',
				label: 'Delete',
				icon: '🗑️',
				shortcut: 'Del',
				action: () => this.deleteElement(target)
			}
		]);

		this.registerMenuProvider('link', (target) => [
			{
				id: 'add-callout',
				label: 'Add Callout',
				icon: '💬',
				action: () => this.addCallout(target)
			},
			{ type: 'separator' },
			{
				id: 'delete',
				label: 'Delete',
				icon: '🗑️',
				shortcut: 'Del',
				action: () => this.deleteElement(target)
			}
		]);

		this.registerMenuProvider('blank', () => [
			{
				id: 'add-element',
				label: 'Add Element',
				icon: '⬜',
				submenu: [
					{
						id: 'add-rectangle',
						label: 'Rectangle',
						action: () => this.addElement('rectangle')
					},
					{
						id: 'add-circle',
						label: 'Circle',
						action: () => this.addElement('circle')
					},
					{
						id: 'add-ellipse',
						label: 'Ellipse',
						action: () => this.addElement('ellipse')
					}
				]
			},
			{ type: 'separator' },
			{
				id: 'paste',
				label: 'Paste',
				icon: '📋',
				shortcut: 'Ctrl+V',
				action: () => this.pasteElements(),
				disabled: !this.hasClipboardContent()
			},
			{ type: 'separator' },
			{
				id: 'select-all',
				label: 'Select All',
				icon: '🔲',
				shortcut: 'Ctrl+A',
				action: () => this.selectAll()
			}
		]);
	}

	/**
	 * Binds event handlers for menu interactions
	 */
	bindEventHandlers() {
		this.eventBus.on(UI_EVENTS.CONTEXT_MENU_REQUESTED, (event) => {
			this.show(event.x, event.y, event.target, event.type);
		});

		this.eventBus.on('state:ui.contextMenu.visible:changed', (event) => {
			if (!event.newValue) {
				this.hide();
			}
		});
	}

	/**
	 * Sets up global event listeners
	 */
	setupEventListeners() {
		document.addEventListener('click', (event) => {
			if (!this.menuElement.contains(event.target)) {
				this.hide();
			}
		});

		document.addEventListener('keydown', (event) => {
			if (event.key === 'Escape' && this.isVisible) {
				this.hide();
			}
		});

		document.addEventListener('contextmenu', (event) => {
			if (!event.target.closest('#ddrCanvas')) {
				this.hide();
			}
		});
	}

	/**
	 * Registers a menu provider for a specific target type
	 */
	registerMenuProvider(targetType, provider) {
		this.customMenuProviders.set(targetType, provider);
	}

	/**
	 * Removes a menu provider
	 */
	unregisterMenuProvider(targetType) {
		return this.customMenuProviders.delete(targetType);
	}

	/**
	 * Shows the context menu at specified coordinates
	 */
	show(x, y, target, targetType) {
		this.currentTarget = target;
		this.currentTargetType = targetType;
		
		const menuItems = this.generateMenuItems(target, targetType);
		this.renderMenu(menuItems);
		this.positionMenu(x, y);
		
		this.menuElement.style.display = 'block';
		this.isVisible = true;

		this.stateStore.setBatch({
			'ui.contextMenu.visible': true,
			'ui.contextMenu.x': x,
			'ui.contextMenu.y': y,
			'ui.contextMenu.target': target
		});

		this.eventBus.emit(UI_EVENTS.CONTEXT_MENU_SHOWN, {
			x, y, target, targetType, menuItems
		});
	}

	/**
	 * Hides the context menu
	 */
	hide() {
		this.menuElement.style.display = 'none';
		this.isVisible = false;
		this.currentTarget = null;
		this.currentTargetType = null;

		this.stateStore.setBatch({
			'ui.contextMenu.visible': false,
			'ui.contextMenu.target': null
		});

		this.eventBus.emit(UI_EVENTS.CONTEXT_MENU_HIDDEN);
	}

	/**
	 * Generates menu items based on target and type
	 */
	generateMenuItems(target, targetType) {
		const provider = this.customMenuProviders.get(targetType);
		if (!provider) {
			return [];
		}

		const items = provider(target);
		return this.processMenuItems(items, target);
	}

	/**
	 * Processes menu items to apply dynamic properties
	 */
	processMenuItems(items, target) {
		return items.map(item => {
			if (item.type === 'separator') {
				return item;
			}

			const processedItem = { ...item };

			if (typeof item.disabled === 'function') {
				processedItem.disabled = item.disabled(target);
			}

			if (typeof item.visible === 'function') {
				processedItem.visible = item.visible(target);
			}

			if (item.submenu) {
				processedItem.submenu = this.processMenuItems(item.submenu, target);
			}

			return processedItem;
		}).filter(item => item.visible !== false);
	}

	/**
	 * Renders the menu items into the DOM
	 */
	renderMenu(items) {
		this.menuElement.innerHTML = '';

		items.forEach(item => {
			if (item.type === 'separator') {
				this.createSeparator();
			} else {
				this.createMenuItem(item);
			}
		});
	}

	/**
	 * Creates a menu item element
	 */
	createMenuItem(item) {
		const itemElement = document.createElement('div');
		itemElement.className = 'context-menu-item';
		
		if (item.disabled) {
			itemElement.classList.add('disabled');
		}

		if (item.submenu) {
			itemElement.classList.add('context-menu-submenu');
		}

		const iconElement = document.createElement('span');
		iconElement.className = 'context-menu-item-icon';
		iconElement.textContent = item.icon || '';

		const textElement = document.createElement('span');
		textElement.className = 'context-menu-item-text';
		textElement.textContent = item.label;

		itemElement.appendChild(iconElement);
		itemElement.appendChild(textElement);

		if (item.shortcut) {
			const shortcutElement = document.createElement('span');
			shortcutElement.className = 'context-menu-item-shortcut';
			shortcutElement.textContent = item.shortcut;
			itemElement.appendChild(shortcutElement);
		}

		if (item.submenu) {
			const indicatorElement = document.createElement('span');
			indicatorElement.className = 'context-menu-submenu-indicator';
			indicatorElement.textContent = '▶';
			itemElement.appendChild(indicatorElement);

			const submenuElement = document.createElement('div');
			submenuElement.className = 'context-menu';
			this.renderSubmenu(submenuElement, item.submenu);
			itemElement.appendChild(submenuElement);
		}

		if (!item.disabled && item.action) {
			itemElement.addEventListener('click', (event) => {
				event.stopPropagation();
				this.handleMenuItemClick(item);
			});
		}

		this.menuElement.appendChild(itemElement);
	}

	/**
	 * Renders a submenu
	 */
	renderSubmenu(submenuElement, items) {
		items.forEach(item => {
			if (item.type === 'separator') {
				const separatorElement = document.createElement('div');
				separatorElement.className = 'context-menu-separator';
				submenuElement.appendChild(separatorElement);
			} else {
				const itemElement = document.createElement('div');
				itemElement.className = 'context-menu-item';
				
				if (item.disabled) {
					itemElement.classList.add('disabled');
				}

				itemElement.innerHTML = `
					<span class="context-menu-item-icon">${item.icon || ''}</span>
					<span class="context-menu-item-text">${item.label}</span>
				`;

				if (!item.disabled && item.action) {
					itemElement.addEventListener('click', (event) => {
						event.stopPropagation();
						this.handleMenuItemClick(item);
					});
				}

				submenuElement.appendChild(itemElement);
			}
		});
	}

	/**
	 * Creates a separator element
	 */
	createSeparator() {
		const separatorElement = document.createElement('div');
		separatorElement.className = 'context-menu-separator';
		this.menuElement.appendChild(separatorElement);
	}

	/**
	 * Handles menu item click
	 */
	handleMenuItemClick(item) {
		this.eventBus.emit(UI_EVENTS.CONTEXT_MENU_ITEM_CLICKED, {
			item,
			target: this.currentTarget,
			targetType: this.currentTargetType
		});

		try {
			if (item.action) {
				item.action(this.currentTarget);
			}
		} catch (error) {
			console.error('ContextMenu: Error executing menu action:', error);
		}

		this.hide();
	}

	/**
	 * Positions the menu to prevent overflow
	 */
	positionMenu(x, y) {
		this.menuElement.style.left = x + 'px';
		this.menuElement.style.top = y + 'px';

		const rect = this.menuElement.getBoundingClientRect();
		const viewportWidth = window.innerWidth;
		const viewportHeight = window.innerHeight;

		if (rect.right > viewportWidth) {
			this.menuElement.style.left = (x - rect.width) + 'px';
		}

		if (rect.bottom > viewportHeight) {
			this.menuElement.style.top = (y - rect.height) + 'px';
		}

		if (rect.left < 0) {
			this.menuElement.style.left = '0px';
		}

		if (rect.top < 0) {
			this.menuElement.style.top = '0px';
		}
	}

	/**
	 * Action: Add callout to element or link
	 */
	addCallout(target) {
		this.eventBus.emit('callout:add', { target });
	}

	/**
	 * Action: Add port to element
	 */
	addPort(target, side) {
		this.eventBus.emit(PORT_EVENTS.ADD, { element: target, side });
	}

	/**
	 * Action: Duplicate element
	 */
	duplicateElement(target) {
		this.eventBus.emit('element:duplicate', { element: target });
	}

	/**
	 * Action: Delete element or link
	 */
	deleteElement(target) {
		this.eventBus.emit('selection:delete-requested', { elements: [target] });
	}

	/**
	 * Action: Add new element
	 */
	addElement(elementType) {
		const menuState = this.stateStore.get('ui.contextMenu');
		this.eventBus.emit('element:create', {
			type: elementType,
			position: { x: menuState.x, y: menuState.y }
		});
	}

	/**
	 * Action: Paste elements from clipboard
	 */
	pasteElements() {
		this.eventBus.emit('clipboard:paste');
	}

	/**
	 * Action: Select all elements
	 */
	selectAll() {
		this.eventBus.emit('selection:select-all');
	}

	/**
	 * Checks if clipboard has content
	 */
	hasClipboardContent() {
		return this.stateStore.get('clipboard.hasContent') || false;
	}

	/**
	 * Adds a custom menu item to existing providers
	 */
	addMenuItem(targetType, item, position = -1) {
		const provider = this.customMenuProviders.get(targetType);
		if (!provider) return false;

		const existingItems = provider();
		if (position === -1) {
			existingItems.push(item);
		} else {
			existingItems.splice(position, 0, item);
		}

		this.customMenuProviders.set(targetType, () => existingItems);
		return true;
	}

	/**
	 * Removes a menu item by ID
	 */
	removeMenuItem(targetType, itemId) {
		const provider = this.customMenuProviders.get(targetType);
		if (!provider) return false;

		const existingItems = provider();
		const index = existingItems.findIndex(item => item.id === itemId);
		
		if (index > -1) {
			existingItems.splice(index, 1);
			this.customMenuProviders.set(targetType, () => existingItems);
			return true;
		}

		return false;
	}

	/**
	 * Gets menu statistics
	 */
	getStats() {
		return {
			providersCount: this.customMenuProviders.size,
			isVisible: this.isVisible,
			currentTarget: this.currentTarget,
			currentTargetType: this.currentTargetType,
			providers: Array.from(this.customMenuProviders.keys())
		};
	}

	/**
	 * Destroys the context menu
	 */
	destroy() {
		this.hide();
		
		if (this.menuElement && this.menuElement.parentNode) {
			this.menuElement.parentNode.removeChild(this.menuElement);
		}

		const styleElement = document.getElementById('context-menu-styles');
		if (styleElement && styleElement.parentNode) {
			styleElement.parentNode.removeChild(styleElement);
		}

		this.customMenuProviders.clear();
		this.initialized = false;

		this.eventBus.emit(UI_EVENTS.CONTEXT_MENU_DESTROYED);
	}
}