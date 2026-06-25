import { PLUGIN_EVENTS, ELEMENT_EVENTS, PAPER_EVENTS, UI_EVENTS } from '../Events/EventTypes.js';

/**
 * GuidelinesPlugin - Advanced guidelines system with smart snapping and visual feedback
 */
export class GuidelinesPlugin {
	constructor(eventBus, stateStore, paperService) {
		this.eventBus = eventBus;
		this.stateStore = stateStore;
		this.paperService = paperService;
		this.initialized = false;
		this.enabled = true;
		this.snapEnabled = true;
		this.showDistanceLabels = true;
		this.customSnapPoints = new Map();
		this.snapHistory = [];
		this.guidanceRules = new Map();
		this.activeSnapSession = null;
		
		this.bindEventHandlers();
	}

	/**
	 * Initializes the guidelines plugin with enhanced features
	 */
	init() {
		if (this.initialized) {
			console.warn('GuidelinesPlugin: Already initialized');
			return;
		}

		this.setupAdvancedGuidelines();
		this.createDistanceMeasurementSystem();
		this.initializeSnapRules();
		this.syncPluginState();
		this.initialized = true;

		this.eventBus.emit(PLUGIN_EVENTS.INITIALIZED, {
			plugin: 'guidelines',
			timestamp: Date.now()
		});
	}

	/**
	 * Sets up advanced guideline features beyond basic UI
	 */
	setupAdvancedGuidelines() {
		this.snapRules = {
			elementToElement: true,
			elementToCanvas: true,
			customPoints: true,
			gridSnap: false,
			distributionGuides: true,
			marginGuides: true
		};

		this.visualOptions = {
			showSnapZones: true,
			showMeasurements: true,
			highlightTargets: true,
			animateSnaps: true
		};

		this.snapTolerance = {
			close: 3,
			medium: 8,
			far: 15
		};
	}

	/**
	 * Creates a distance measurement overlay system
	 */
	createDistanceMeasurementSystem() {
		const measurementContainer = document.createElement('div');
		measurementContainer.id = 'guidelines-measurements';
		measurementContainer.className = 'guidelines-measurements';
		
		measurementContainer.style.cssText = `
			position: absolute;
			top: 0;
			left: 0;
			width: 100%;
			height: 100%;
			pointer-events: none;
			z-index: 1100;
		`;

		const paperElement = this.paperService.paperElement || document.querySelector('#ddrCanvas');
		if (paperElement) {
			paperElement.appendChild(measurementContainer);
		}

		this.measurementContainer = measurementContainer;
		this.addMeasurementStyles();
	}

	/**
	 * Adds CSS styles for measurement displays and enhanced guidelines
	 */
	addMeasurementStyles() {
		const styleId = 'guidelines-plugin-styles';
		if (document.getElementById(styleId)) return;

		const style = document.createElement('style');
		style.id = styleId;
		style.textContent = `
			.distance-label {
				position: absolute;
				background: rgba(0, 0, 0, 0.8);
				color: white;
				padding: 2px 6px;
				border-radius: 3px;
				font-family: Arial, sans-serif;
				font-size: 11px;
				font-weight: bold;
				white-space: nowrap;
				transform: translate(-50%, -50%);
				z-index: 1102;
			}
			
			.snap-zone {
				position: absolute;
				border: 1px dashed #4ecdc4;
				background: rgba(78, 205, 196, 0.1);
				pointer-events: none;
				z-index: 1050;
			}
			
			.guideline-enhanced {
				box-shadow: 0 0 4px rgba(255, 107, 107, 0.6);
				animation: guidelineEnhancedPulse 1.5s ease-in-out infinite;
			}
			
			.snap-target-highlight {
				position: absolute;
				border: 2px solid #4ecdc4;
				background: rgba(78, 205, 196, 0.2);
				border-radius: 2px;
				pointer-events: none;
				z-index: 1051;
				animation: snapTargetPulse 1s ease-in-out infinite;
			}
			
			.distribution-guide {
				position: absolute;
				background: #9b59b6;
				pointer-events: none;
				z-index: 1001;
			}
			
			.distribution-guide.horizontal {
				height: 1px;
			}
			
			.distribution-guide.vertical {
				width: 1px;
			}
			
			.margin-guide {
				position: absolute;
				border: 1px solid #e74c3c;
				background: rgba(231, 76, 60, 0.1);
				pointer-events: none;
				z-index: 1001;
			}
			
			@keyframes guidelineEnhancedPulse {
				0%, 100% { opacity: 0.7; }
				50% { opacity: 1; }
			}
			
			@keyframes snapTargetPulse {
				0%, 100% { transform: scale(1); opacity: 0.7; }
				50% { transform: scale(1.05); opacity: 1; }
			}
		`;
		
		document.head.appendChild(style);
	}

	/**
	 * Initializes intelligent snap rules and behaviors
	 */
	initializeSnapRules() {
		this.addGuidanceRule('equalSpacing', (elements) => {
			return this.calculateEqualSpacingGuides(elements);
		});

		this.addGuidanceRule('alignment', (draggedElement, otherElements) => {
			return this.calculateAlignmentGuides(draggedElement, otherElements);
		});

		this.addGuidanceRule('margins', (element, containerBounds) => {
			return this.calculateMarginGuides(element, containerBounds);
		});

		this.addGuidanceRule('distribution', (elements) => {
			return this.calculateDistributionGuides(elements);
		});
	}

	/**
	 * Synchronizes plugin state with global state store
	 */
	syncPluginState() {
		const pluginState = this.stateStore.get('plugins.guidelines') || {};
		this.enabled = pluginState.enabled !== false;
		this.snapEnabled = pluginState.snapEnabled !== false;
		this.showDistanceLabels = pluginState.showDistanceLabels !== false;
	}

	/**
	 * Binds event handlers for enhanced guideline operations
	 */
	bindEventHandlers() {
		this.eventBus.on(PAPER_EVENTS.ELEMENT_POINTERDOWN, (event) => {
			this.startAdvancedGuidanceSession(event);
		});

		this.eventBus.on(PAPER_EVENTS.ELEMENT_POINTERMOVE, (event) => {
			this.updateAdvancedGuidance(event);
		});

		this.eventBus.on(PAPER_EVENTS.ELEMENT_POINTERUP, (event) => {
			this.completeGuidanceSession(event);
		});

		this.eventBus.on(UI_EVENTS.GUIDELINES_UPDATED, (event) => {
			this.enhanceStandardGuidelines(event);
		});

		this.eventBus.on('guidelines:add-custom-point', (event) => {
			this.addCustomSnapPoint(event.point, event.options);
		});

		this.eventBus.on('guidelines:remove-custom-point', (event) => {
			this.removeCustomSnapPoint(event.pointId);
		});

		this.eventBus.on('guidelines:set-snap-enabled', (event) => {
			this.setSnapEnabled(event.enabled);
		});

		this.eventBus.on('state:plugins.guidelines:changed', (event) => {
			this.updatePluginState(event.newValue);
		});
	}

	/**
	 * Starts an advanced guidance session when element dragging begins
	 */
	startAdvancedGuidanceSession(event) {
		if (!this.enabled) return;

		this.activeSnapSession = {
			draggedElement: event.element,
			startPosition: event.element.position(),
			startTime: Date.now(),
			snapEvents: [],
			activeGuides: new Set()
		};

		this.calculateInitialGuidanceContext();
		this.highlightPotentialSnapTargets();
	}

	/**
	 * Updates advanced guidance during element movement
	 */
	updateAdvancedGuidance(event) {
		if (!this.enabled || !this.activeSnapSession) return;

		const currentPosition = event.element.position();
		const elementBounds = event.element.getBBox();

		this.clearEnhancedVisuals();
		
		if (this.visualOptions.showSnapZones) {
			this.renderSnapZones(elementBounds);
		}

		if (this.visualOptions.showMeasurements) {
			this.renderDistanceMeasurements(elementBounds);
		}

		if (this.snapRules.distributionGuides) {
			this.renderDistributionGuides(event.element);
		}

		if (this.snapRules.marginGuides) {
			this.renderMarginGuides(elementBounds);
		}

		this.applyIntelligentSnapping(event.element, currentPosition);
	}

	/**
	 * Completes the guidance session and records snap events
	 */
	completeGuidanceSession(event) {
		if (!this.activeSnapSession) return;

		const finalPosition = event.element.position();
		const sessionDuration = Date.now() - this.activeSnapSession.startTime;

		this.snapHistory.push({
			element: event.element,
			startPosition: this.activeSnapSession.startPosition,
			finalPosition: finalPosition,
			duration: sessionDuration,
			snapEvents: this.activeSnapSession.snapEvents,
			timestamp: Date.now()
		});

		this.clearEnhancedVisuals();
		this.clearSnapTargetHighlights();
		this.activeSnapSession = null;

		this.eventBus.emit(PLUGIN_EVENTS.SNAP_APPLIED, {
			element: event.element,
			finalPosition,
			sessionData: this.snapHistory[this.snapHistory.length - 1]
		});
	}

	/**
	 * Enhances standard guidelines with additional visual feedback
	 */
	enhanceStandardGuidelines(event) {
		if (!this.enabled || !event.guidelines) return;

		event.guidelines.forEach((guideline, index) => {
			if (guideline.distance <= this.snapTolerance.close) {
				this.addGuidelineEnhancement(guideline, 'close');
			} else if (guideline.distance <= this.snapTolerance.medium) {
				this.addGuidelineEnhancement(guideline, 'medium');
			}
		});
	}

	/**
	 * Adds visual enhancement to guidelines based on proximity
	 */
	addGuidelineEnhancement(guideline, proximityLevel) {
		const guidelines = document.querySelectorAll('.guideline');
		guidelines.forEach(el => {
			if (this.matchesGuideline(el, guideline)) {
				el.classList.add('guideline-enhanced');
				el.dataset.proximity = proximityLevel;
			}
		});
	}

	/**
	 * Determines if DOM element matches guideline data
	 */
	matchesGuideline(element, guideline) {
		const elementRect = element.getBoundingClientRect();
		
		if (guideline.type === 'vertical') {
			return Math.abs(elementRect.left - guideline.x) < 2;
		} else {
			return Math.abs(elementRect.top - guideline.y) < 2;
		}
	}

	/**
	 * Renders snap zones around potential target areas
	 */
	renderSnapZones(elementBounds) {
		const snapZones = this.calculateSnapZones(elementBounds);
		
		snapZones.forEach((zone, index) => {
			const zoneElement = document.createElement('div');
			zoneElement.className = 'snap-zone';
			zoneElement.style.cssText = `
				left: ${zone.x}px;
				top: ${zone.y}px;
				width: ${zone.width}px;
				height: ${zone.height}px;
			`;
			
			this.measurementContainer.appendChild(zoneElement);
		});
	}

	/**
	 * Calculates snap zones based on nearby elements and snap points
	 */
	calculateSnapZones(elementBounds) {
		const zones = [];
		const tolerance = this.snapTolerance.medium;

		for (const snapData of this.getSnapPoints().values()) {
			const zoneWidth = tolerance * 2;
			const zoneHeight = tolerance * 2;

			zones.push({
				x: snapData.left - tolerance,
				y: snapData.top - tolerance,
				width: zoneWidth,
				height: zoneHeight,
				type: 'alignment',
				snapData
			});
		}

		return zones;
	}

	/**
	 * Renders distance measurements between elements
	 */
	renderDistanceMeasurements(elementBounds) {
		if (!this.showDistanceLabels) return;

		const nearbyElements = this.findNearbyElements(elementBounds);
		
		nearbyElements.forEach(otherElement => {
			const otherBounds = otherElement.getBBox();
			const distance = this.calculateDistance(elementBounds, otherBounds);
			
			if (distance < 100) {
				this.createDistanceLabel(elementBounds, otherBounds, distance);
			}
		});
	}

	/**
	 * Creates a distance label between two elements
	 */
	createDistanceLabel(bounds1, bounds2, distance) {
		const labelElement = document.createElement('div');
		labelElement.className = 'distance-label';
		labelElement.textContent = `${Math.round(distance)}px`;

		const midX = (bounds1.x + bounds1.width / 2 + bounds2.x + bounds2.width / 2) / 2;
		const midY = (bounds1.y + bounds1.height / 2 + bounds2.y + bounds2.height / 2) / 2;

		labelElement.style.left = midX + 'px';
		labelElement.style.top = midY + 'px';

		this.measurementContainer.appendChild(labelElement);
	}

	/**
	 * Renders distribution guides for evenly spaced elements
	 */
	renderDistributionGuides(draggedElement) {
		const distributionGuides = this.guidanceRules.get('distribution');
		if (!distributionGuides) return;

		const allElements = this.getAllElements().filter(el => el !== draggedElement);
		const guides = distributionGuides(allElements);

		guides.forEach(guide => {
			const guideElement = document.createElement('div');
			guideElement.className = `distribution-guide ${guide.orientation}`;
			
			if (guide.orientation === 'horizontal') {
				guideElement.style.cssText = `
					left: ${guide.start}px;
					top: ${guide.position}px;
					width: ${guide.length}px;
				`;
			} else {
				guideElement.style.cssText = `
					left: ${guide.position}px;
					top: ${guide.start}px;
					height: ${guide.length}px;
				`;
			}

			this.measurementContainer.appendChild(guideElement);
		});
	}

	/**
	 * Renders margin guides showing consistent spacing from canvas edges
	 */
	renderMarginGuides(elementBounds) {
		const canvasBounds = this.getCanvasBounds();
		const marginGuides = this.calculateMarginGuides(elementBounds, canvasBounds);

		marginGuides.forEach(guide => {
			const guideElement = document.createElement('div');
			guideElement.className = 'margin-guide';
			guideElement.style.cssText = `
				left: ${guide.x}px;
				top: ${guide.y}px;
				width: ${guide.width}px;
				height: ${guide.height}px;
			`;

			this.measurementContainer.appendChild(guideElement);
		});
	}

	/**
	 * Applies intelligent snapping based on proximity and context
	 */
	applyIntelligentSnapping(element, currentPosition) {
		if (!this.snapEnabled) return currentPosition;

		let snappedPosition = { ...currentPosition };
		let snapApplied = false;

		const elementBounds = element.getBBox();
		const snapCandidates = this.findSnapCandidates(elementBounds);

		snapCandidates.forEach(candidate => {
			if (candidate.distance <= this.snapTolerance.close) {
				if (candidate.type === 'vertical') {
					snappedPosition.x = candidate.snapPosition.x;
					snapApplied = true;
				} else {
					snappedPosition.y = candidate.snapPosition.y;
					snapApplied = true;
				}

				this.recordSnapEvent(candidate);
			}
		});

		if (snapApplied && this.visualOptions.animateSnaps) {
			element.position(snappedPosition);
			this.animateSnapFeedback(element);
		}

		return snappedPosition;
	}

	/**
	 * Records a snap event for analysis and history
	 */
	recordSnapEvent(snapCandidate) {
		if (this.activeSnapSession) {
			this.activeSnapSession.snapEvents.push({
				type: snapCandidate.type,
				target: snapCandidate.target,
				distance: snapCandidate.distance,
				timestamp: Date.now()
			});
		}
	}

	/**
	 * Provides visual feedback when snap occurs
	 */
	animateSnapFeedback(element) {
		const elementView = this.paperService.paper.findViewByModel(element);
		if (elementView) {
			const el = elementView.el;
			el.style.transition = 'transform 0.15s ease-out';
			el.style.transform = 'scale(1.02)';
			
			setTimeout(() => {
				el.style.transform = 'scale(1)';
				setTimeout(() => {
					el.style.transition = '';
				}, 150);
			}, 75);
		}
	}

	/**
	 * Highlights potential snap targets during dragging
	 */
	highlightPotentialSnapTargets() {
		if (!this.visualOptions.highlightTargets) return;

		const allElements = this.getAllElements();
		allElements.forEach(element => {
			if (element !== this.activeSnapSession.draggedElement) {
				this.addSnapTargetHighlight(element);
			}
		});
	}

	/**
	 * Adds visual highlight to potential snap target
	 */
	addSnapTargetHighlight(element) {
		const bounds = element.getBBox();
		const highlight = document.createElement('div');
		highlight.className = 'snap-target-highlight';
		highlight.style.cssText = `
			left: ${bounds.x - 2}px;
			top: ${bounds.y - 2}px;
			width: ${bounds.width + 4}px;
			height: ${bounds.height + 4}px;
		`;

		this.measurementContainer.appendChild(highlight);
	}

	/**
	 * Clears enhanced visual elements
	 */
	clearEnhancedVisuals() {
		const elements = this.measurementContainer.querySelectorAll('.distance-label, .snap-zone, .distribution-guide, .margin-guide');
		elements.forEach(el => el.remove());

		const enhancedGuidelines = document.querySelectorAll('.guideline-enhanced');
		enhancedGuidelines.forEach(el => {
			el.classList.remove('guideline-enhanced');
			delete el.dataset.proximity;
		});
	}

	/**
	 * Clears snap target highlights
	 */
	clearSnapTargetHighlights() {
		const highlights = this.measurementContainer.querySelectorAll('.snap-target-highlight');
		highlights.forEach(el => el.remove());
	}

	/**
	 * Adds a custom guidance rule
	 */
	addGuidanceRule(name, ruleFunction) {
		this.guidanceRules.set(name, ruleFunction);
	}

	/**
	 * Removes a guidance rule
	 */
	removeGuidanceRule(name) {
		return this.guidanceRules.delete(name);
	}

	/**
	 * Adds a custom snap point
	 */
	addCustomSnapPoint(point, options = {}) {
		const pointId = options.id || this.generateSnapPointId();
		
		this.customSnapPoints.set(pointId, {
			id: pointId,
			x: point.x,
			y: point.y,
			type: options.type || 'custom',
			priority: options.priority || 0,
			label: options.label || '',
			created: Date.now()
		});

		this.eventBus.emit(PLUGIN_EVENTS.GUIDELINE_CREATED, {
			pointId,
			point,
			options
		});

		return pointId;
	}

	/**
	 * Removes a custom snap point
	 */
	removeCustomSnapPoint(pointId) {
		const removed = this.customSnapPoints.delete(pointId);
		
		if (removed) {
			this.eventBus.emit(PLUGIN_EVENTS.GUIDELINE_REMOVED, {
				pointId
			});
		}

		return removed;
	}

	/**
	 * Sets snap functionality enabled or disabled
	 */
	setSnapEnabled(enabled) {
		this.snapEnabled = enabled;
		this.stateStore.set('plugins.guidelines.snapEnabled', enabled);
	}

	/**
	 * Calculates initial guidance context for session
	 */
	calculateInitialGuidanceContext() {
		if (!this.activeSnapSession) return;

		const draggedElement = this.activeSnapSession.draggedElement;
		const allElements = this.getAllElements().filter(el => el !== draggedElement);
		
		this.activeSnapSession.context = {
			totalElements: allElements.length,
			nearbyElements: this.findNearbyElements(draggedElement.getBBox()),
			canvasBounds: this.getCanvasBounds()
		};
	}

	/**
	 * Finds elements near the specified bounds
	 */
	findNearbyElements(bounds) {
		const allElements = this.getAllElements();
		const threshold = 200;

		return allElements.filter(element => {
			const elementBounds = element.getBBox();
			const distance = this.calculateDistance(bounds, elementBounds);
			return distance < threshold;
		});
	}

	/**
	 * Calculates distance between two bounding boxes
	 */
	calculateDistance(bounds1, bounds2) {
		const centerX1 = bounds1.x + bounds1.width / 2;
		const centerY1 = bounds1.y + bounds1.height / 2;
		const centerX2 = bounds2.x + bounds2.width / 2;
		const centerY2 = bounds2.y + bounds2.height / 2;

		return Math.sqrt(Math.pow(centerX2 - centerX1, 2) + Math.pow(centerY2 - centerY1, 2));
	}

	/**
	 * Gets all snap points including custom points
	 */
	getSnapPoints() {
		const snapPoints = new Map();

		const allElements = this.getAllElements();
		allElements.forEach(element => {
			const bounds = element.getBBox();
			snapPoints.set(element.id, {
				element,
				left: bounds.x,
				right: bounds.x + bounds.width,
				centerX: bounds.x + bounds.width / 2,
				top: bounds.y,
				bottom: bounds.y + bounds.height,
				centerY: bounds.y + bounds.height / 2
			});
		});

		for (const [id, point] of this.customSnapPoints) {
			snapPoints.set(id, point);
		}

		return snapPoints;
	}

	/**
	 * Finds snap candidates for current element position
	 */
	findSnapCandidates(elementBounds) {
		const candidates = [];
		const snapPoints = this.getSnapPoints();

		for (const snapData of snapPoints.values()) {
			const verticalCandidates = [
				{ position: snapData.left, alignment: 'left' },
				{ position: snapData.right, alignment: 'right' },
				{ position: snapData.centerX, alignment: 'center' }
			];

			const horizontalCandidates = [
				{ position: snapData.top, alignment: 'top' },
				{ position: snapData.bottom, alignment: 'bottom' },
				{ position: snapData.centerY, alignment: 'center' }
			];

			verticalCandidates.forEach(candidate => {
				const distance = Math.abs(elementBounds.x - candidate.position);
				if (distance <= this.snapTolerance.far) {
					candidates.push({
						type: 'vertical',
						target: snapData.element,
						distance,
						snapPosition: { x: candidate.position, y: elementBounds.y },
						alignment: candidate.alignment
					});
				}
			});

			horizontalCandidates.forEach(candidate => {
				const distance = Math.abs(elementBounds.y - candidate.position);
				if (distance <= this.snapTolerance.far) {
					candidates.push({
						type: 'horizontal',
						target: snapData.element,
						distance,
						snapPosition: { x: elementBounds.x, y: candidate.position },
						alignment: candidate.alignment
					});
				}
			});
		}

		return candidates.sort((a, b) => a.distance - b.distance);
	}

	/**
	 * Calculates equal spacing guides for element distribution
	 */
	calculateEqualSpacingGuides(elements) {
		if (elements.length < 3) return [];

		const guides = [];
		const sortedByX = elements.sort((a, b) => a.getBBox().x - b.getBBox().x);
		const sortedByY = elements.sort((a, b) => a.getBBox().y - b.getBBox().y);

		if (sortedByX.length >= 3) {
			const spacing = this.calculateAverageSpacing(sortedByX, 'horizontal');
			guides.push(...this.generateSpacingGuides(sortedByX, spacing, 'horizontal'));
		}

		if (sortedByY.length >= 3) {
			const spacing = this.calculateAverageSpacing(sortedByY, 'vertical');
			guides.push(...this.generateSpacingGuides(sortedByY, spacing, 'vertical'));
		}

		return guides;
	}

	/**
	 * Calculates margin guides for consistent canvas spacing
	 */
	calculateMarginGuides(elementBounds, canvasBounds) {
		const guides = [];
		const commonMargins = [10, 20, 30, 50];

		commonMargins.forEach(margin => {
			if (Math.abs(elementBounds.x - margin) < this.snapTolerance.medium) {
				guides.push({
					type: 'margin-left',
					x: 0,
					y: elementBounds.y,
					width: margin,
					height: elementBounds.height,
					margin
				});
			}

			if (Math.abs(elementBounds.y - margin) < this.snapTolerance.medium) {
				guides.push({
					type: 'margin-top',
					x: elementBounds.x,
					y: 0,
					width: elementBounds.width,
					height: margin,
					margin
				});
			}
		});

		return guides;
	}

	/**
	 * Calculates distribution guides for evenly distributed elements
	 */
	calculateDistributionGuides(elements) {
		return this.calculateEqualSpacingGuides(elements);
	}

	/**
	 * Calculates alignment guides between elements
	 */
	calculateAlignmentGuides(draggedElement, otherElements) {
		const guides = [];
		const draggedBounds = draggedElement.getBBox();

		otherElements.forEach(otherElement => {
			const otherBounds = otherElement.getBBox();

			if (Math.abs(draggedBounds.x - otherBounds.x) < this.snapTolerance.medium) {
				guides.push({
					type: 'alignment-left',
					orientation: 'vertical',
					position: otherBounds.x,
					start: Math.min(draggedBounds.y, otherBounds.y),
					length: Math.max(draggedBounds.y + draggedBounds.height, otherBounds.y + otherBounds.height)
				});
			}
		});

		return guides;
	}

	/**
	 * Gets all elements from the graph
	 */
	getAllElements() {
		try {
			const graphService = this.stateStore.get('services.graphService');
			return graphService ? graphService.graph.getElements() : [];
		} catch {
			return [];
		}
	}

	/**
	 * Gets current canvas bounds
	 */
	getCanvasBounds() {
		const canvasState = this.stateStore.get('canvas');
		return {
			width: canvasState.width || 800,
			height: canvasState.height || 600
		};
	}

	/**
	 * Generates unique snap point identifier
	 */
	generateSnapPointId() {
		return `snap_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
	}

	/**
	 * Updates plugin state in response to changes
	 */
	updatePluginState(newState) {
		this.enabled = newState.enabled !== false;
		this.snapEnabled = newState.snapEnabled !== false;
		this.showDistanceLabels = newState.showDistanceLabels !== false;
	}

	/**
	 * Gets plugin statistics and performance data
	 */
	getStats() {
		return {
			enabled: this.enabled,
			snapEnabled: this.snapEnabled,
			showDistanceLabels: this.showDistanceLabels,
			customSnapPoints: this.customSnapPoints.size,
			guidanceRules: this.guidanceRules.size,
			snapHistoryEntries: this.snapHistory.length,
			activeSession: !!this.activeSnapSession,
			lastSnapTime: this.snapHistory.length > 0 ? this.snapHistory[this.snapHistory.length - 1].timestamp : null
		};
	}

	/**
	 * Enables or disables the plugin
	 */
	setEnabled(enabled) {
		this.enabled = enabled;
		this.stateStore.set('plugins.guidelines.enabled', enabled);

		if (!enabled) {
			this.clearEnhancedVisuals();
			this.clearSnapTargetHighlights();
		}
	}

	/**
	 * Destroys the plugin and cleans up resources
	 */
	destroy() {
		this.clearEnhancedVisuals();
		this.clearSnapTargetHighlights();

		if (this.measurementContainer && this.measurementContainer.parentNode) {
			this.measurementContainer.parentNode.removeChild(this.measurementContainer);
		}

		const styleElement = document.getElementById('guidelines-plugin-styles');
		if (styleElement && styleElement.parentNode) {
			styleElement.parentNode.removeChild(styleElement);
		}

		this.customSnapPoints.clear();
		this.guidanceRules.clear();
		this.snapHistory.length = 0;
		this.activeSnapSession = null;
		this.initialized = false;

		this.eventBus.emit(PLUGIN_EVENTS.DESTROYED, {
			plugin: 'guidelines',
			timestamp: Date.now()
		});
	}
}