import { UI_EVENTS, PAPER_EVENTS } from '../Events/EventTypes.js';

/**
 * Guidelines - Visual alignment and snapping system for precise element positioning
 */
export class Guidelines {
	constructor(eventBus, stateStore) {
		this.eventBus = eventBus;
		this.stateStore = stateStore;
		this.paperElement = null;
		this.guidelinesContainer = null;
		this.initialized = false;
		this.activeGuidelines = new Map();
		this.snapDistance = 8;
		this.enabled = true;
		this.showSnapIndicators = true;
		this.draggedElement = null;
		this.dragOriginalPosition = null;
		this.snapPoints = new Map();
		
		this.bindEventHandlers();
	}

	/**
	 * Initializes the guidelines system
	 */
	init() {
		if (this.initialized) {
			console.warn('Guidelines: Already initialized');
			return;
		}

		this.findPaperElement();
		this.createGuidelinesContainer();
		this.setupConfiguration();
		this.initialized = true;

		this.eventBus.emit(UI_EVENTS.GUIDELINES_INITIALIZED);
	}

	/**
	 * Locates the paper element in the DOM
	 */
	findPaperElement() {
		this.paperElement = document.querySelector('#ddrCanvas');
		if (!this.paperElement) {
			throw new Error('Guidelines: Canvas element not found');
		}
	}

	/**
	 * Creates the guidelines container for rendering
	 */
	createGuidelinesContainer() {
		this.guidelinesContainer = document.createElement('div');
		this.guidelinesContainer.id = 'guidelines-container';
		this.guidelinesContainer.className = 'guidelines-container';
		
		this.guidelinesContainer.style.cssText = `
			position: absolute;
			top: 0;
			left: 0;
			width: 100%;
			height: 100%;
			pointer-events: none;
			z-index: 1000;
			overflow: hidden;
		`;

		this.paperElement.style.position = 'relative';
		this.paperElement.appendChild(this.guidelinesContainer);
		this.addGuidelineStyles();
	}

	/**
	 * Adds CSS styles for guideline elements
	 */
	addGuidelineStyles() {
		const styleId = 'guidelines-styles';
		if (document.getElementById(styleId)) return;

		const style = document.createElement('style');
		style.id = styleId;
		style.textContent = `
			.guideline {
				position: absolute;
				pointer-events: none;
				z-index: 1001;
			}
			
			.guideline-vertical {
				width: 1px;
				background: #ff6b6b;
				box-shadow: 0 0 2px rgba(255, 107, 107, 0.5);
			}
			
			.guideline-horizontal {
				height: 1px;
				background: #ff6b6b;
				box-shadow: 0 0 2px rgba(255, 107, 107, 0.5);
			}
			
			.guideline-animated {
				animation: guidelinePulse 1s ease-in-out infinite alternate;
			}
			
			.snap-indicator {
				position: absolute;
				width: 8px;
				height: 8px;
				background: #4ecdc4;
				border: 2px solid white;
				border-radius: 50%;
				transform: translate(-50%, -50%);
				z-index: 1002;
				animation: snapIndicatorPulse 0.5s ease-in-out infinite alternate;
			}
			
			@keyframes guidelinePulse {
				from { opacity: 0.6; }
				to { opacity: 1; }
			}
			
			@keyframes snapIndicatorPulse {
				from { transform: translate(-50%, -50%) scale(0.8); }
				to { transform: translate(-50%, -50%) scale(1.2); }
			}
			
			.guidelines-disabled .guideline {
				display: none;
			}
		`;
		
		document.head.appendChild(style);
	}

	/**
	 * Sets up configuration from state store
	 */
	setupConfiguration() {
		const guidelinesConfig = this.stateStore.get('ui.guidelines');
		this.snapDistance = guidelinesConfig.snapDistance || 8;
		this.enabled = guidelinesConfig.enabled !== false;
		this.showSnapIndicators = guidelinesConfig.showSnapIndicators !== false;

		if (!this.enabled) {
			this.guidelinesContainer.classList.add('guidelines-disabled');
		}
	}

	/**
	 * Binds event handlers for guideline interactions
	 */
	bindEventHandlers() {
		this.eventBus.on(PAPER_EVENTS.ELEMENT_POINTERDOWN, (event) => {
			this.handleElementDragStart(event);
		});

		this.eventBus.on(PAPER_EVENTS.ELEMENT_POINTERMOVE, (event) => {
			this.handleElementDragMove(event);
		});

		this.eventBus.on(PAPER_EVENTS.ELEMENT_POINTERUP, (event) => {
			this.handleElementDragEnd(event);
		});

		this.eventBus.on(PAPER_EVENTS.BLANK_POINTERDOWN, () => {
			this.clearGuidelines();
		});

		this.eventBus.on('state:ui.guidelines:changed', (event) => {
			this.updateConfiguration(event.newValue);
		});

		this.eventBus.on('guidelines:toggle', () => {
			this.toggleGuidelines();
		});

		this.eventBus.on('guidelines:clear', () => {
			this.clearGuidelines();
		});
	}

	/**
	 * Handles the start of element dragging
	 */
	handleElementDragStart(event) {
		if (!this.enabled) return;

		this.draggedElement = event.element;
		this.dragOriginalPosition = event.element.position();
		this.calculateSnapPoints();
	}

	/**
	 * Handles element movement during dragging
	 */
	handleElementDragMove(event) {
		if (!this.enabled || !this.draggedElement) return;

		const currentPosition = this.draggedElement.position();
		const elementBounds = this.draggedElement.getBBox();
		
		this.clearGuidelines();
		const guidelines = this.calculateGuidelines(elementBounds, currentPosition);
		this.renderGuidelines(guidelines);
		
		if (this.showSnapIndicators) {
			this.renderSnapIndicators(guidelines, elementBounds);
		}

		this.eventBus.emit(UI_EVENTS.GUIDELINES_UPDATED, {
			element: this.draggedElement,
			guidelines,
			position: currentPosition
		});
	}

	/**
	 * Handles the end of element dragging
	 */
	handleElementDragEnd(event) {
		if (!this.enabled) return;

		this.clearGuidelines();
		this.draggedElement = null;
		this.dragOriginalPosition = null;
		this.snapPoints.clear();
	}

	/**
	 * Calculates snap points from all other elements
	 */
	calculateSnapPoints() {
		this.snapPoints.clear();

		const graphService = this.getGraphService();
		if (!graphService) return;

		const allElements = graphService.graph.getElements();
		
		allElements.forEach(element => {
			if (element === this.draggedElement) return;

			const bounds = element.getBBox();
			const snapData = {
				element,
				left: bounds.x,
				right: bounds.x + bounds.width,
				centerX: bounds.x + bounds.width / 2,
				top: bounds.y,
				bottom: bounds.y + bounds.height,
				centerY: bounds.y + bounds.height / 2
			};

			this.snapPoints.set(element.id, snapData);
		});
	}

	/**
	 * Calculates guidelines based on element position and other elements
	 */
	calculateGuidelines(elementBounds, currentPosition) {
		const guidelines = [];
		const paperBounds = this.getPaperBounds();

		const elementLeft = elementBounds.x;
		const elementRight = elementBounds.x + elementBounds.width;
		const elementCenterX = elementBounds.x + elementBounds.width / 2;
		const elementTop = elementBounds.y;
		const elementBottom = elementBounds.y + elementBounds.height;
		const elementCenterY = elementBounds.y + elementBounds.height / 2;

		for (const snapData of this.snapPoints.values()) {
			this.checkVerticalAlignment(guidelines, elementLeft, elementRight, elementCenterX, snapData, paperBounds);
			this.checkHorizontalAlignment(guidelines, elementTop, elementBottom, elementCenterY, snapData, paperBounds);
		}

		this.checkCanvasAlignment(guidelines, elementLeft, elementRight, elementCenterX, elementTop, elementBottom, elementCenterY, paperBounds);

		return this.removeDuplicateGuidelines(guidelines);
	}

	/**
	 * Checks for vertical alignment opportunities
	 */
	checkVerticalAlignment(guidelines, elementLeft, elementRight, elementCenterX, snapData, paperBounds) {
		const alignments = [
			{ elementPos: elementLeft, snapPos: snapData.left, type: 'left' },
			{ elementPos: elementLeft, snapPos: snapData.right, type: 'left-to-right' },
			{ elementPos: elementRight, snapPos: snapData.right, type: 'right' },
			{ elementPos: elementRight, snapPos: snapData.left, type: 'right-to-left' },
			{ elementPos: elementCenterX, snapPos: snapData.centerX, type: 'center' }
		];

		alignments.forEach(alignment => {
			const distance = Math.abs(alignment.elementPos - alignment.snapPos);
			if (distance <= this.snapDistance) {
				guidelines.push({
					type: 'vertical',
					x: alignment.snapPos,
					alignmentType: alignment.type,
					distance,
					snapElement: snapData.element,
					bounds: {
						top: 0,
						bottom: paperBounds.height
					}
				});
			}
		});
	}

	/**
	 * Checks for horizontal alignment opportunities
	 */
	checkHorizontalAlignment(guidelines, elementTop, elementBottom, elementCenterY, snapData, paperBounds) {
		const alignments = [
			{ elementPos: elementTop, snapPos: snapData.top, type: 'top' },
			{ elementPos: elementTop, snapPos: snapData.bottom, type: 'top-to-bottom' },
			{ elementPos: elementBottom, snapPos: snapData.bottom, type: 'bottom' },
			{ elementPos: elementBottom, snapPos: snapData.top, type: 'bottom-to-top' },
			{ elementPos: elementCenterY, snapPos: snapData.centerY, type: 'center' }
		];

		alignments.forEach(alignment => {
			const distance = Math.abs(alignment.elementPos - alignment.snapPos);
			if (distance <= this.snapDistance) {
				guidelines.push({
					type: 'horizontal',
					y: alignment.snapPos,
					alignmentType: alignment.type,
					distance,
					snapElement: snapData.element,
					bounds: {
						left: 0,
						right: paperBounds.width
					}
				});
			}
		});
	}

	/**
	 * Checks for canvas edge alignment
	 */
	checkCanvasAlignment(guidelines, elementLeft, elementRight, elementCenterX, elementTop, elementBottom, elementCenterY, paperBounds) {
		const canvasAlignments = [
			{ pos: elementLeft, snapPos: 0, type: 'vertical', coord: 'x', alignmentType: 'canvas-left' },
			{ pos: elementRight, snapPos: paperBounds.width, type: 'vertical', coord: 'x', alignmentType: 'canvas-right' },
			{ pos: elementCenterX, snapPos: paperBounds.width / 2, type: 'vertical', coord: 'x', alignmentType: 'canvas-center' },
			{ pos: elementTop, snapPos: 0, type: 'horizontal', coord: 'y', alignmentType: 'canvas-top' },
			{ pos: elementBottom, snapPos: paperBounds.height, type: 'horizontal', coord: 'y', alignmentType: 'canvas-bottom' },
			{ pos: elementCenterY, snapPos: paperBounds.height / 2, type: 'horizontal', coord: 'y', alignmentType: 'canvas-center' }
		];

		canvasAlignments.forEach(alignment => {
			const distance = Math.abs(alignment.pos - alignment.snapPos);
			if (distance <= this.snapDistance) {
				const guideline = {
					type: alignment.type,
					alignmentType: alignment.alignmentType,
					distance,
					snapElement: null
				};

				if (alignment.type === 'vertical') {
					guideline.x = alignment.snapPos;
					guideline.bounds = { top: 0, bottom: paperBounds.height };
				} else {
					guideline.y = alignment.snapPos;
					guideline.bounds = { left: 0, right: paperBounds.width };
				}

				guidelines.push(guideline);
			}
		});
	}

	/**
	 * Removes duplicate guidelines
	 */
	removeDuplicateGuidelines(guidelines) {
		const unique = [];
		const seen = new Set();

		guidelines.forEach(guideline => {
			const key = guideline.type === 'vertical' 
				? `v_${guideline.x}` 
				: `h_${guideline.y}`;

			if (!seen.has(key)) {
				seen.add(key);
				unique.push(guideline);
			}
		});

		return unique.sort((a, b) => a.distance - b.distance);
	}

	/**
	 * Renders guidelines on the canvas
	 */
	renderGuidelines(guidelines) {
		guidelines.forEach((guideline, index) => {
			const guidelineElement = this.createGuidelineElement(guideline);
			this.guidelinesContainer.appendChild(guidelineElement);
			this.activeGuidelines.set(`guideline_${index}`, guidelineElement);
		});

		this.eventBus.emit(UI_EVENTS.GUIDELINES_SHOWN, {
			count: guidelines.length,
			guidelines
		});
	}

	/**
	 * Creates a visual guideline element
	 */
	createGuidelineElement(guideline) {
		const element = document.createElement('div');
		element.className = `guideline guideline-${guideline.type}`;

		if (guideline.type === 'vertical') {
			element.style.left = guideline.x + 'px';
			element.style.top = guideline.bounds.top + 'px';
			element.style.height = (guideline.bounds.bottom - guideline.bounds.top) + 'px';
		} else {
			element.style.top = guideline.y + 'px';
			element.style.left = guideline.bounds.left + 'px';
			element.style.width = (guideline.bounds.right - guideline.bounds.left) + 'px';
		}

		if (guideline.distance === 0) {
			element.classList.add('guideline-animated');
		}

		return element;
	}

	/**
	 * Renders snap indicators at alignment points
	 */
	renderSnapIndicators(guidelines, elementBounds) {
		guidelines.filter(g => g.distance <= 2).forEach((guideline, index) => {
			const indicatorElement = this.createSnapIndicator(guideline, elementBounds);
			this.guidelinesContainer.appendChild(indicatorElement);
			this.activeGuidelines.set(`indicator_${index}`, indicatorElement);
		});
	}

	/**
	 * Creates a snap indicator element
	 */
	createSnapIndicator(guideline, elementBounds) {
		const indicator = document.createElement('div');
		indicator.className = 'snap-indicator';

		let x, y;

		if (guideline.type === 'vertical') {
			x = guideline.x;
			y = elementBounds.y + elementBounds.height / 2;
		} else {
			x = elementBounds.x + elementBounds.width / 2;
			y = guideline.y;
		}

		indicator.style.left = x + 'px';
		indicator.style.top = y + 'px';

		return indicator;
	}

	/**
	 * Clears all visible guidelines
	 */
	clearGuidelines() {
		this.activeGuidelines.forEach(element => {
			if (element.parentNode) {
				element.parentNode.removeChild(element);
			}
		});

		this.activeGuidelines.clear();

		this.eventBus.emit(UI_EVENTS.GUIDELINES_HIDDEN);
	}

	/**
	 * Toggles guidelines on/off
	 */
	toggleGuidelines() {
		this.enabled = !this.enabled;
		this.stateStore.set('ui.guidelines.enabled', this.enabled);

		this.guidelinesContainer.classList.toggle('guidelines-disabled', !this.enabled);

		if (!this.enabled) {
			this.clearGuidelines();
		}

		this.eventBus.emit(UI_EVENTS.GUIDELINES_TOGGLED, {
			enabled: this.enabled
		});
	}

	/**
	 * Updates configuration from state changes
	 */
	updateConfiguration(newConfig) {
		this.snapDistance = newConfig.snapDistance || this.snapDistance;
		this.enabled = newConfig.enabled !== false;
		this.showSnapIndicators = newConfig.showSnapIndicators !== false;

		this.guidelinesContainer.classList.toggle('guidelines-disabled', !this.enabled);

		if (!this.enabled) {
			this.clearGuidelines();
		}
	}

	/**
	 * Gets the current paper bounds
	 */
	getPaperBounds() {
		const canvasState = this.stateStore.get('canvas');
		return {
			width: canvasState.width || 800,
			height: canvasState.height || 600
		};
	}

	/**
	 * Gets the graph service instance
	 */
	getGraphService() {
		try {
			return this.stateStore.get('services.graphService');
		} catch {
			return null;
		}
	}

	/**
	 * Applies snap positioning to an element
	 */
	applySnapping(element, newPosition) {
		if (!this.enabled) return newPosition;

		const elementBounds = element.getBBox();
		elementBounds.x = newPosition.x;
		elementBounds.y = newPosition.y;

		const guidelines = this.calculateGuidelines(elementBounds, newPosition);
		
		let snappedX = newPosition.x;
		let snappedY = newPosition.y;

		guidelines.forEach(guideline => {
			if (guideline.distance <= this.snapDistance) {
				if (guideline.type === 'vertical') {
					switch (guideline.alignmentType) {
						case 'left':
						case 'canvas-left':
							snappedX = guideline.x;
							break;
						case 'right':
						case 'canvas-right':
							snappedX = guideline.x - elementBounds.width;
							break;
						case 'center':
						case 'canvas-center':
							snappedX = guideline.x - elementBounds.width / 2;
							break;
					}
				} else {
					switch (guideline.alignmentType) {
						case 'top':
						case 'canvas-top':
							snappedY = guideline.y;
							break;
						case 'bottom':
						case 'canvas-bottom':
							snappedY = guideline.y - elementBounds.height;
							break;
						case 'center':
						case 'canvas-center':
							snappedY = guideline.y - elementBounds.height / 2;
							break;
					}
				}
			}
		});

		return { x: snappedX, y: snappedY };
	}

	/**
	 * Gets guideline statistics
	 */
	getStats() {
		return {
			enabled: this.enabled,
			snapDistance: this.snapDistance,
			showSnapIndicators: this.showSnapIndicators,
			activeGuidelinesCount: this.activeGuidelines.size,
			snapPointsCount: this.snapPoints.size,
			isDragging: !!this.draggedElement
		};
	}

	/**
	 * Sets snap distance
	 */
	setSnapDistance(distance) {
		this.snapDistance = Math.max(1, Math.min(50, distance));
		this.stateStore.set('ui.guidelines.snapDistance', this.snapDistance);
	}

	/**
	 * Enables or disables snap indicators
	 */
	setShowSnapIndicators(show) {
		this.showSnapIndicators = show;
		this.stateStore.set('ui.guidelines.showSnapIndicators', show);
	}

	/**
	 * Destroys the guidelines system
	 */
	destroy() {
		this.clearGuidelines();

		if (this.guidelinesContainer && this.guidelinesContainer.parentNode) {
			this.guidelinesContainer.parentNode.removeChild(this.guidelinesContainer);
		}

		const styleElement = document.getElementById('guidelines-styles');
		if (styleElement && styleElement.parentNode) {
			styleElement.parentNode.removeChild(styleElement);
		}

		this.activeGuidelines.clear();
		this.snapPoints.clear();
		this.initialized = false;

		this.eventBus.emit(UI_EVENTS.GUIDELINES_DESTROYED);
	}
}