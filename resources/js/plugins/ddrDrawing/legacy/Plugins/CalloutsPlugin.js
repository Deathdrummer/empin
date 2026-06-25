import { PLUGIN_EVENTS, ELEMENT_EVENTS, LINK_EVENTS } from '../Events/EventTypes.js';

/**
 * CalloutsPlugin - Manages callout annotations and text overlays for elements and links
 */
export class CalloutsPlugin {
	constructor(eventBus, stateStore, graphService) {
		this.eventBus = eventBus;
		this.stateStore = stateStore;
		this.graphService = graphService;
		this.initialized = false;
		this.callouts = new Map();
		this.editingOverlay = null;
		this.paperElement = null;
		this.enabled = true;
		
		this.bindEventHandlers();
	}

	/**
	 * Initializes the callouts plugin
	 */
	init() {
		if (this.initialized) {
			console.warn('CalloutsPlugin: Already initialized');
			return;
		}

		this.findPaperElement();
		this.setupCalloutStyles();
		this.syncPluginState();
		this.initialized = true;

		this.eventBus.emit(PLUGIN_EVENTS.INITIALIZED, {
			plugin: 'callouts',
			timestamp: Date.now()
		});
	}

	/**
	 * Locates the paper element for overlay positioning
	 */
	findPaperElement() {
		this.paperElement = document.querySelector('#ddrCanvas');
		if (!this.paperElement) {
			throw new Error('CalloutsPlugin: Canvas element not found');
		}
		this.paperElement.style.position = 'relative';
	}

	/**
	 * Adds CSS styles for callout elements
	 */
	setupCalloutStyles() {
		const styleId = 'callouts-plugin-styles';
		if (document.getElementById(styleId)) return;

		const style = document.createElement('style');
		style.id = styleId;
		style.textContent = `
			.callout-outer-container {
				position: absolute;
				pointer-events: none;
				width: 1px;
				height: 1px;
				z-index: 1000;
			}
			
			.callout-text-overlay {
				position: absolute;
				bottom: 5px;
				left: 0;
				font-family: Arial, sans-serif;
				font-size: 12px;
				color: #333;
				pointer-events: auto;
				cursor: pointer;
				user-select: none;
				white-space: pre-wrap;
				word-break: break-word;
				min-width: max-content;
				max-width: 500px;
				line-height: 12px;
				background: rgba(255, 255, 255, 0.9);
				padding: 2px 4px;
				border-radius: 2px;
				box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
			}
			
			.callout-text-overlay.placeholder {
				color: #999;
				font-style: italic;
			}
			
			.callout-text-overlay:hover {
				background: rgba(255, 255, 255, 1);
				box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
			}
			
			.callout-line {
				position: absolute;
				background: #333;
				pointer-events: none;
				z-index: 999;
			}
			
			.callout-line.diagonal {
				transform-origin: left center;
			}
			
			.callout-line.horizontal {
				height: 1px;
			}
			
			.callout-line.vertical {
				width: 1px;
			}
			
			.callout-editing {
				position: absolute;
				font-family: Arial, sans-serif;
				font-size: 12px;
				color: #333;
				background: white;
				border: 1px solid #007bff;
				border-radius: 2px;
				outline: none;
				min-width: 80px;
				max-width: 500px;
				line-height: 14px;
				padding: 2px 4px;
				white-space: pre;
				word-wrap: normal;
				resize: none;
				z-index: 2000;
			}
		`;
		
		document.head.appendChild(style);
	}

	/**
	 * Synchronizes plugin state with global state store
	 */
	syncPluginState() {
		const pluginState = this.stateStore.get('plugins.callouts');
		this.enabled = pluginState.enabled !== false;
		this.editingOverlay = pluginState.editing || null;
	}

	/**
	 * Binds event handlers for callout operations
	 */
	bindEventHandlers() {
		this.eventBus.on('callout:add', (event) => {
			this.addCallout(event.target);
		});

		this.eventBus.on('callout:remove', (event) => {
			this.removeCallout(event.target, event.calloutId);
		});

		this.eventBus.on('callout:edit', (event) => {
			this.editCallout(event.overlay);
		});

		this.eventBus.on(ELEMENT_EVENTS.MOVED, (event) => {
			this.updateElementCallouts(event.element);
		});

		this.eventBus.on(ELEMENT_EVENTS.REMOVED, (event) => {
			this.removeElementCallouts(event.element);
		});

		this.eventBus.on(LINK_EVENTS.REMOVED, (event) => {
			this.removeLinkCallouts(event.link);
		});

		this.eventBus.on('state:plugins.callouts:changed', (event) => {
			this.updatePluginState(event.newValue);
		});
	}

	/**
	 * Adds a callout to an element or link
	 */
	addCallout(target, options = {}) {
		if (!this.enabled || !target) return null;

		const {
			text = 'Enter text...',
			position = null,
			autoEdit = true
		} = options;

		let calloutId;
		if (target.isElement()) {
			calloutId = this.addElementCallout(target, text, position);
		} else if (target.isLink()) {
			calloutId = this.addLinkCallout(target, text, position);
		} else {
			console.warn('CalloutsPlugin: Invalid target for callout');
			return null;
		}

		if (calloutId && autoEdit && text === 'Enter text...') {
			setTimeout(() => {
				const callout = this.callouts.get(calloutId);
				if (callout && callout.textOverlay) {
					this.startEditingOverlay(callout.textOverlay);
				}
			}, 50);
		}

		this.updatePluginState({
			...this.stateStore.get('plugins.callouts'),
			overlays: this.callouts
		});

		this.eventBus.emit(PLUGIN_EVENTS.CALLOUT_ADDED, {
			target,
			calloutId,
			text
		});

		return calloutId;
	}

	/**
	 * Adds a callout to an element
	 */
	addElementCallout(element, text, customPosition = null) {
		const calloutId = this.generateCalloutId();
		const bbox = element.getBBox();
		
		const startX = bbox.x + bbox.width;
		const startY = bbox.y + bbox.height / 2;
		const midX = startX + 40;
		const midY = startY - 30;

		if (customPosition) {
			midX = customPosition.x;
			midY = customPosition.y;
		}

		const diagonalLine = this.createCalloutLine(startX, startY, midX, midY, 'diagonal');
		const horizontalLine = this.createCalloutLine(midX, midY, midX + 100, midY, 'horizontal');
		const textOverlay = this.createTextOverlay(midX + 2, midY, text);

		const calloutData = {
			id: calloutId,
			target: element,
			targetType: 'element',
			text: text,
			diagonalLine: diagonalLine,
			horizontalLine: horizontalLine,
			textOverlay: textOverlay,
			startPosition: { x: startX, y: startY },
			midPosition: { x: midX, y: midY },
			created: Date.now()
		};

		this.callouts.set(calloutId, calloutData);
		this.addCalloutToElement(element, calloutId);

		setTimeout(() => {
			this.makeCalloutLinesNonInteractive(diagonalLine, horizontalLine);
		}, 10);

		return calloutId;
	}

	/**
	 * Adds a callout to a link
	 */
	addLinkCallout(link, text, customPosition = null) {
		const calloutId = this.generateCalloutId();
		
		const currentLabels = link.prop('labels') || [];
		const position = customPosition || { distance: 0.5, offset: 0 };

		const labelConfig = {
			markup: [
				{
					tagName: 'g',
					selector: 'calloutGroup',
					children: [
						{
							tagName: 'line',
							selector: 'calloutLine'
						},
						{
							tagName: 'line',
							selector: 'calloutLineHorizontal'
						},
						{
							tagName: 'text',
							selector: 'calloutText'
						}
					]
				}
			],
			attrs: {
				calloutLine: {
					stroke: '#333',
					strokeWidth: 1,
					x1: 0,
					y1: 0,
					x2: 30,
					y2: -40
				},
				calloutLineHorizontal: {
					stroke: '#333',
					strokeWidth: 1,
					x1: 30,
					y1: -40,
					x2: 80,
					y2: -40
				},
				calloutText: {
					text: text,
					fill: '#333',
					fontSize: 12,
					fontFamily: 'Arial, sans-serif',
					textAnchor: 'start',
					x: 82,
					y: -36
				}
			},
			position: position,
			calloutId: calloutId
		};

		currentLabels.push(labelConfig);
		link.prop('labels', currentLabels);

		const calloutData = {
			id: calloutId,
			target: link,
			targetType: 'link',
			text: text,
			labelIndex: currentLabels.length - 1,
			position: position,
			created: Date.now()
		};

		this.callouts.set(calloutId, calloutData);

		return calloutId;
	}

	/**
	 * Creates a callout line element
	 */
	createCalloutLine(x1, y1, x2, y2, type) {
		const line = document.createElement('div');
		line.className = `callout-line ${type}`;
		
		if (type === 'diagonal') {
			const length = Math.sqrt(Math.pow(x2 - x1, 2) + Math.pow(y2 - y1, 2));
			const angle = Math.atan2(y2 - y1, x2 - x1) * 180 / Math.PI;
			
			line.style.cssText = `
				left: ${x1}px;
				top: ${y1}px;
				width: ${length}px;
				height: 1px;
				transform: rotate(${angle}deg);
			`;
		} else if (type === 'horizontal') {
			line.style.cssText = `
				left: ${x1}px;
				top: ${y1}px;
				width: ${x2 - x1}px;
				height: 1px;
			`;
		}

		this.paperElement.appendChild(line);
		return line;
	}

	/**
	 * Creates a text overlay element
	 */
	createTextOverlay(x, y, text) {
		const outerContainer = document.createElement('div');
		outerContainer.className = 'callout-outer-container';
		outerContainer.style.cssText = `
			left: ${x}px;
			top: ${y}px;
		`;

		const innerContainer = document.createElement('div');
		innerContainer.className = 'callout-text-overlay';
		innerContainer.textContent = text;

		if (text === 'Enter text...') {
			innerContainer.classList.add('placeholder');
		}

		outerContainer.appendChild(innerContainer);
		this.paperElement.appendChild(outerContainer);

		innerContainer.addEventListener('dblclick', (event) => {
			event.stopPropagation();
			this.startEditingOverlay(innerContainer);
		});

		innerContainer.outerContainer = outerContainer;
		return innerContainer;
	}

	/**
	 * Makes callout lines non-interactive
	 */
	makeCalloutLinesNonInteractive(...lines) {
		lines.forEach(line => {
			if (line) {
				line.style.pointerEvents = 'none';
			}
		});
	}

	/**
	 * Starts editing a text overlay
	 */
	startEditingOverlay(overlay) {
		if (!overlay || this.editingOverlay) return;

		const currentText = overlay.textContent;
		const isPlaceholder = currentText === 'Enter text...';

		const editableDiv = document.createElement('textarea');
		editableDiv.className = 'callout-editing';
		editableDiv.value = isPlaceholder ? '' : currentText;

		const outerContainer = overlay.outerContainer;
		const outerRect = outerContainer.getBoundingClientRect();
		const paperRect = this.paperElement.getBoundingClientRect();

		editableDiv.style.cssText = `
			left: ${outerRect.left - paperRect.left}px;
			bottom: ${paperRect.bottom - outerRect.top + 5}px;
		`;

		overlay.style.display = 'none';
		this.paperElement.appendChild(editableDiv);
		
		editableDiv.focus();
		editableDiv.select();

		this.editingOverlay = editableDiv;
		this.stateStore.set('plugins.callouts.editing', editableDiv);

		let isFinished = false;

		const finishEditing = () => {
			if (isFinished || !editableDiv.parentNode) return;
			isFinished = true;

			const newText = editableDiv.value.trim() || 'Enter text...';
			this.updateOverlayText(overlay, newText);

			overlay.style.display = 'block';
			this.paperElement.removeChild(editableDiv);

			this.editingOverlay = null;
			this.stateStore.set('plugins.callouts.editing', null);

			this.updateCalloutForElement(overlay);

			document.removeEventListener('click', outsideClickHandler);
			
			this.eventBus.emit(PLUGIN_EVENTS.CALLOUT_EDITED, {
				overlay,
				newText,
				previousText: currentText
			});
		};

		const cancelEditing = () => {
			if (isFinished || !editableDiv.parentNode) return;
			isFinished = true;
			
			overlay.style.display = 'block';
			this.paperElement.removeChild(editableDiv);
			
			this.editingOverlay = null;
			this.stateStore.set('plugins.callouts.editing', null);
			
			document.removeEventListener('click', outsideClickHandler);
		};

		const outsideClickHandler = (event) => {
			if (!editableDiv.contains(event.target)) {
				finishEditing();
			}
		};

		setTimeout(() => {
			document.addEventListener('click', outsideClickHandler);
		}, 10);

		editableDiv.addEventListener('blur', finishEditing);
		editableDiv.addEventListener('keydown', (event) => {
			if (event.key === 'Enter' && !event.shiftKey) {
				event.preventDefault();
				finishEditing();
			} else if (event.key === 'Escape') {
				event.preventDefault();
				cancelEditing();
			}
		});
	}

	/**
	 * Updates overlay text content
	 */
	updateOverlayText(overlay, newText) {
		const isPlaceholder = newText === 'Enter text...';
		overlay.textContent = newText;
		overlay.classList.toggle('placeholder', isPlaceholder);

		const calloutId = this.findCalloutByOverlay(overlay);
		if (calloutId) {
			const callout = this.callouts.get(calloutId);
			if (callout) {
				callout.text = newText;
				callout.modified = Date.now();
			}
		}
	}

	/**
	 * Updates callout positioning for moved elements
	 */
	updateCalloutForElement(overlay) {
		const calloutId = this.findCalloutByOverlay(overlay);
		if (!calloutId) return;

		const callout = this.callouts.get(calloutId);
		if (!callout || callout.targetType !== 'element') return;

		this.updateElementCallouts(callout.target);
	}

	/**
	 * Updates all callouts for a specific element
	 */
	updateElementCallouts(element) {
		const elementCallouts = this.getElementCallouts(element);
		
		elementCallouts.forEach(callout => {
			if (callout.targetType === 'element') {
				this.repositionElementCallout(callout);
			}
		});
	}

	/**
	 * Repositions a callout for an element
	 */
	repositionElementCallout(callout) {
		const bbox = callout.target.getBBox();
		const startX = bbox.x + bbox.width;
		const startY = bbox.y + bbox.height / 2;
		const midX = startX + 40;
		const midY = startY - 30;

		if (callout.diagonalLine) {
			const length = Math.sqrt(Math.pow(midX - startX, 2) + Math.pow(midY - startY, 2));
			const angle = Math.atan2(midY - startY, midX - startX) * 180 / Math.PI;
			
			callout.diagonalLine.style.cssText = `
				left: ${startX}px;
				top: ${startY}px;
				width: ${length}px;
				height: 1px;
				transform: rotate(${angle}deg);
				background: #333;
				pointer-events: none;
			`;
		}

		if (callout.textOverlay && callout.textOverlay.outerContainer) {
			const outerContainer = callout.textOverlay.outerContainer;
			outerContainer.style.left = (midX + 2) + 'px';
			outerContainer.style.top = midY + 'px';

			const overlayRect = callout.textOverlay.getBoundingClientRect();
			const overlayWidth = overlayRect.width;

			if (callout.horizontalLine) {
				callout.horizontalLine.style.cssText = `
					left: ${midX}px;
					top: ${midY}px;
					width: ${overlayWidth + 10}px;
					height: 1px;
					background: #333;
					pointer-events: none;
				`;
			}
		}

		callout.startPosition = { x: startX, y: startY };
		callout.midPosition = { x: midX, y: midY };
	}

	/**
	 * Removes a specific callout
	 */
	removeCallout(target, calloutId) {
		if (!calloutId) {
			this.removeTargetCallouts(target);
			return;
		}

		const callout = this.callouts.get(calloutId);
		if (!callout) return;

		this.destroyCallout(callout);
		this.callouts.delete(calloutId);

		if (callout.targetType === 'element') {
			this.removeCalloutFromElement(callout.target, calloutId);
		}

		this.eventBus.emit(PLUGIN_EVENTS.CALLOUT_REMOVED, {
			target,
			calloutId
		});
	}

	/**
	 * Removes all callouts for a target
	 */
	removeTargetCallouts(target) {
		if (target.isElement()) {
			this.removeElementCallouts(target);
		} else if (target.isLink()) {
			this.removeLinkCallouts(target);
		}
	}

	/**
	 * Removes all callouts for an element
	 */
	removeElementCallouts(element) {
		const elementCallouts = this.getElementCallouts(element);
		
		elementCallouts.forEach(callout => {
			this.destroyCallout(callout);
			this.callouts.delete(callout.id);
		});

		this.clearElementCallouts(element);
	}

	/**
	 * Removes all callouts for a link
	 */
	removeLinkCallouts(link) {
		const linkCallouts = this.getLinkCallouts(link);
		
		linkCallouts.forEach(callout => {
			this.callouts.delete(callout.id);
		});

		link.prop('labels', []);
	}

	/**
	 * Destroys callout visual elements
	 */
	destroyCallout(callout) {
		if (callout.diagonalLine && callout.diagonalLine.parentNode) {
			callout.diagonalLine.parentNode.removeChild(callout.diagonalLine);
		}

		if (callout.horizontalLine && callout.horizontalLine.parentNode) {
			callout.horizontalLine.parentNode.removeChild(callout.horizontalLine);
		}

		if (callout.textOverlay && callout.textOverlay.outerContainer && callout.textOverlay.outerContainer.parentNode) {
			callout.textOverlay.outerContainer.parentNode.removeChild(callout.textOverlay.outerContainer);
		}
	}

	/**
	 * Gets all callouts for an element
	 */
	getElementCallouts(element) {
		return Array.from(this.callouts.values()).filter(callout => 
			callout.target === element && callout.targetType === 'element'
		);
	}

	/**
	 * Gets all callouts for a link
	 */
	getLinkCallouts(link) {
		return Array.from(this.callouts.values()).filter(callout => 
			callout.target === link && callout.targetType === 'link'
		);
	}

	/**
	 * Finds callout by text overlay
	 */
	findCalloutByOverlay(overlay) {
		for (const [id, callout] of this.callouts) {
			if (callout.textOverlay === overlay) {
				return id;
			}
		}
		return null;
	}

	/**
	 * Adds callout reference to element properties
	 */
	addCalloutToElement(element, calloutId) {
		const callouts = element.prop('callouts') || [];
		callouts.push(calloutId);
		element.prop('callouts', callouts);
	}

	/**
	 * Removes callout reference from element properties
	 */
	removeCalloutFromElement(element, calloutId) {
		const callouts = element.prop('callouts') || [];
		const index = callouts.indexOf(calloutId);
		if (index > -1) {
			callouts.splice(index, 1);
			element.prop('callouts', callouts);
		}
	}

	/**
	 * Clears all callout references from element
	 */
	clearElementCallouts(element) {
		element.prop('callouts', []);
	}

	/**
	 * Generates unique callout identifier
	 */
	generateCalloutId() {
		return `callout_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
	}

	/**
	 * Updates plugin state in response to changes
	 */
	updatePluginState(newState) {
		this.enabled = newState.enabled !== false;
		this.editingOverlay = newState.editing || null;
	}

	/**
	 * Gets plugin statistics
	 */
	getStats() {
		const elementCallouts = Array.from(this.callouts.values()).filter(c => c.targetType === 'element').length;
		const linkCallouts = Array.from(this.callouts.values()).filter(c => c.targetType === 'link').length;

		return {
			enabled: this.enabled,
			totalCallouts: this.callouts.size,
			elementCallouts,
			linkCallouts,
			isEditing: !!this.editingOverlay
		};
	}

	/**
	 * Enables or disables the plugin
	 */
	setEnabled(enabled) {
		this.enabled = enabled;
		this.stateStore.set('plugins.callouts.enabled', enabled);
	}

	/**
	 * Destroys the plugin
	 */
	destroy() {
		Array.from(this.callouts.values()).forEach(callout => {
			this.destroyCallout(callout);
		});

		this.callouts.clear();

		if (this.editingOverlay && this.editingOverlay.parentNode) {
			this.editingOverlay.parentNode.removeChild(this.editingOverlay);
		}

		const styleElement = document.getElementById('callouts-plugin-styles');
		if (styleElement && styleElement.parentNode) {
			styleElement.parentNode.removeChild(styleElement);
		}

		this.initialized = false;

		this.eventBus.emit(PLUGIN_EVENTS.DESTROYED, {
			plugin: 'callouts',
			timestamp: Date.now()
		});
	}
}