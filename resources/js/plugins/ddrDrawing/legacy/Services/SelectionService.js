 
/**
 * SelectionService - Service for managing element selection and multi-selection
 */
export class SelectionService {
	constructor(eventBus, stateStore, graphService, portService) {
		this.eventBus = eventBus;
		this.stateStore = stateStore;
		this.graphService = graphService;
		this.portService = portService;
		this.initialized = false;
		this.debugMode = false;
		this.selectionBox = null;
		this.selectionTimeout = null;
		
		this.bindEventHandlers();
	}

	/**
	 * Initializes the selection service
	 */
	init() {
		if (this.initialized) {
			console.warn('SelectionService: Already initialized');
			return;
		}

		this.initialized = true;
		this.eventBus.emit('selection:service-initialized');
	}

	/**
	 * Binds service to external events
	 */
	bindEventHandlers() {
		this.eventBus.on('paper:element-click', (event) => {
			this.handleElementSelection(event.element, event.originalEvent);
		});
		
		this.eventBus.on('paper:link-click', (event) => {
			this.handleLinkSelection(event.link, event.originalEvent);
		});
		
		this.eventBus.on('paper:blank-click', (event) => {
			this.handleBlankAreaClick(event);
		});
		
		this.eventBus.on('selection:select-element', (event) => {
			this.selectElement(event.element, event.addToSelection);
		});
		
		this.eventBus.on('selection:select-multiple', (event) => {
			this.selectMultiple(event.elements);
		});
		
		this.eventBus.on('selection:clear', () => {
			this.clearSelection();
		});
		
		this.eventBus.on('selection:delete-requested', () => {
			this.deleteSelected();
		});
		
		this.eventBus.on('selection:invert', () => {
			this.invertSelection();
		});
		
		this.eventBus.on('selection:select-all', () => {
			this.selectAll();
		});
	}

	/**
	 * Handles element selection with mouse interaction
	 */
	handleElementSelection(element, originalEvent) {
		const isShiftPressed = originalEvent.shiftKey;
		const isCtrlPressed = originalEvent.ctrlKey || originalEvent.metaKey;
		
		if (isCtrlPressed || isShiftPressed) {
			this.toggleElementSelection(element);
		} else {
			this.selectElement(element, false);
		}
	}

	/**
	 * Handles link selection with mouse interaction
	 */
	handleLinkSelection(link, originalEvent) {
		const isShiftPressed = originalEvent.shiftKey;
		const isCtrlPressed = originalEvent.ctrlKey || originalEvent.metaKey;
		
		if (isCtrlPressed || isShiftPressed) {
			this.toggleElementSelection(link);
		} else {
			this.selectElement(link, false);
		}
	}

	/**
	 * Handles blank area click for clearing selection
	 */
	handleBlankAreaClick(event) {
		this.clearSelection();
	}

	/**
	 * Selects an element or adds it to selection
	 */
	selectElement(element, addToSelection = false) {
		const currentSelection = this.stateStore.get('selection.elements');
		
		if (!addToSelection) {
			this.clearSelectionStyles(currentSelection);
			this.stateStore.set('selection.elements', [element]);
		} else {
			if (!currentSelection.includes(element)) {
				currentSelection.push(element);
				this.stateStore.set('selection.elements', currentSelection);
			}
		}

		this.applySelectionStyle(element);
		this.updateSelectionState();
		this.handleSelectionSideEffects(element);

		this.eventBus.emit('selection:element-selected', {
			element,
			addToSelection,
			totalSelected: this.stateStore.get('selection.elements').length
		});
	}

	/**
	 * Toggles element selection state
	 */
	toggleElementSelection(element) {
		const currentSelection = this.stateStore.get('selection.elements');
		const elementIndex = currentSelection.indexOf(element);

		if (elementIndex === -1) {
			this.selectElement(element, true);
		} else {
			this.unselectElement(element);
		}
	}

	/**
	 * Unselects a specific element
	 */
	unselectElement(element) {
		const currentSelection = this.stateStore.get('selection.elements');
		const elementIndex = currentSelection.indexOf(element);

		if (elementIndex > -1) {
			currentSelection.splice(elementIndex, 1);
			this.stateStore.set('selection.elements', currentSelection);
			this.removeSelectionStyle(element);
			this.updateSelectionState();

			this.eventBus.emit('selection:element-unselected', {
				element,
				totalSelected: currentSelection.length
			});
		}
	}

	/**
	 * Selects multiple elements at once
	 */
	selectMultiple(elements) {
		const currentSelection = this.stateStore.get('selection.elements');
		this.clearSelectionStyles(currentSelection);

		const validElements = elements.filter(el => 
			this.graphService.graph.getCells().includes(el)
		);

		validElements.forEach(element => {
			this.applySelectionStyle(element);
		});

		this.stateStore.set('selection.elements', validElements);
		this.updateSelectionState();

		this.eventBus.emit('selection:multiple-selected', {
			elements: validElements,
			count: validElements.length
		});
	}

	/**
	 * Selects elements within a rectangular area
	 */
	selectInArea(rect) {
		const elementsInArea = this.graphService.getElementsInArea(rect);
		this.selectMultiple(elementsInArea);

		this.eventBus.emit('selection:area-selected', {
			rect,
			elements: elementsInArea,
			count: elementsInArea.length
		});
	}

	/**
	 * Selects all elements in the graph
	 */
	selectAll() {
		const allCells = this.graphService.graph.getCells();
		this.selectMultiple(allCells);

		this.eventBus.emit('selection:all-selected', {
			count: allCells.length
		});
	}

	/**
	 * Inverts current selection
	 */
	invertSelection() {
		const allCells = this.graphService.graph.getCells();
		const currentSelection = this.stateStore.get('selection.elements');
		const invertedSelection = allCells.filter(cell => 
			!currentSelection.includes(cell)
		);

		this.selectMultiple(invertedSelection);

		this.eventBus.emit('selection:inverted', {
			previousCount: currentSelection.length,
			newCount: invertedSelection.length
		});
	}

	/**
	 * Clears all selection
	 */
	clearSelection() {
		const currentSelection = this.stateStore.get('selection.elements');
		
		if (currentSelection.length === 0) return;

		this.clearSelectionStyles(currentSelection);
		this.hideConnectedPorts(currentSelection);

		this.stateStore.setBatch({
			'selection.elements': [],
			'selection.type': null,
			'selection.boundingBox': null,
			'selection.lastSelected': null
		});

		this.eventBus.emit('selection:cleared', {
			previousCount: currentSelection.length
		});
	}

	/**
	 * Applies visual selection style to an element
	 */
	applySelectionStyle(element) {
		if (element.isElement()) {
			element.attr('body/stroke', '#ff4444');
			element.attr('body/strokeWidth', 3);
		} else if (element.isLink()) {
			element.attr('line/stroke', '#31d0c6');
			element.attr('line/strokeWidth', 3);
			this.showConnectedElementPorts(element);
		}
	}

	/**
	 * Removes visual selection style from an element
	 */
	removeSelectionStyle(element) {
		if (element.isElement()) {
			element.attr('body/stroke', '#8a8a96');
			element.attr('body/strokeWidth', 1);
		} else if (element.isLink()) {
			element.attr('line/stroke', '#8a8a96');
			element.attr('line/strokeWidth', 2);
			this.hideConnectedElementPorts(element);
		}
	}

	/**
	 * Clears selection styles from multiple elements
	 */
	clearSelectionStyles(elements) {
		elements.forEach(element => {
			this.removeSelectionStyle(element);
		});
	}

	/**
	 * Shows ports for elements connected to selected links
	 */
	showConnectedElementPorts(link) {
		const sourceElement = link.getSourceElement();
		const targetElement = link.getTargetElement();

		if (sourceElement) {
			this.portService.showElementPorts(sourceElement);
		}

		if (targetElement && targetElement !== sourceElement) {
			this.portService.showElementPorts(targetElement);
		}
	}

	/**
	 * Hides ports for elements connected to unselected links
	 */
	hideConnectedElementPorts(link) {
		const sourceElement = link.getSourceElement();
		const targetElement = link.getTargetElement();
		const currentSelection = this.stateStore.get('selection.elements');

		if (sourceElement && !currentSelection.includes(sourceElement)) {
			this.portService.hideElementPorts(sourceElement);
		}

		if (targetElement && 
			targetElement !== sourceElement && 
			!currentSelection.includes(targetElement)) {
			this.portService.hideElementPorts(targetElement);
		}
	}

	/**
	 * Hides ports for all connected elements in selection
	 */
	hideConnectedPorts(elements) {
		elements.forEach(element => {
			if (element.isLink()) {
				this.hideConnectedElementPorts(element);
			}
		});
	}

	/**
	 * Handles side effects of selection changes
	 */
	handleSelectionSideEffects(element) {
		if (element.isElement()) {
			this.portService.showElementPorts(element);
		}
	}

	/**
	 * Updates selection state in store
	 */
	updateSelectionState() {
		const currentSelection = this.stateStore.get('selection.elements');
		
		let selectionType = null;
		let boundingBox = null;
		let lastSelected = null;

		if (currentSelection.length === 1) {
			const element = currentSelection[0];
			selectionType = element.isElement() ? 'element' : 'link';
			lastSelected = element;
			
			if (element.isElement()) {
				boundingBox = element.getBBox();
			}
		} else if (currentSelection.length > 1) {
			selectionType = 'multiple';
			lastSelected = currentSelection[currentSelection.length - 1];
			boundingBox = this.calculateSelectionBoundingBox(currentSelection);
		}

		this.stateStore.setBatch({
			'selection.type': selectionType,
			'selection.boundingBox': boundingBox,
			'selection.lastSelected': lastSelected
		});
	}

	/**
	 * Calculates bounding box for multiple selected elements
	 */
	calculateSelectionBoundingBox(elements) {
		const elementBounds = elements
			.filter(el => el.isElement())
			.map(el => el.getBBox());

		if (elementBounds.length === 0) return null;

		const bbox = joint.g.rect();
		elementBounds.forEach(bound => {
			bbox.union(bound);
		});

		return bbox;
	}

	/**
	 * Deletes all selected elements
	 */
	deleteSelected() {
		const currentSelection = this.stateStore.get('selection.elements');
		
		if (currentSelection.length === 0) return;

		const elementsToDelete = [...currentSelection];
		this.clearSelection();

		elementsToDelete.forEach(element => {
			if (element.isElement()) {
				this.portService.cleanupElementPorts(element);
			}
			element.remove();
		});

		this.eventBus.emit('selection:deleted', {
			deletedElements: elementsToDelete,
			count: elementsToDelete.length
		});
	}

	/**
	 * Groups selected elements together
	 */
	groupSelected() {
		const currentSelection = this.stateStore.get('selection.elements');
		const elements = currentSelection.filter(el => el.isElement());

		if (elements.length < 2) return null;

		const group = this.graphService.groupElements(elements);
		this.selectElement(group, false);

		this.eventBus.emit('selection:grouped', {
			group,
			elements,
			count: elements.length
		});

		return group;
	}

	/**
	 * Gets elements by their selection state
	 */
	getSelectedElements() {
		return this.stateStore.get('selection.elements');
	}

	/**
	 * Gets selected elements of specific type
	 */
	getSelectedElementsByType(type) {
		const selected = this.getSelectedElements();
		
		if (type === 'element') {
			return selected.filter(el => el.isElement());
		} else if (type === 'link') {
			return selected.filter(el => el.isLink());
		}
		
		return selected;
	}

	/**
	 * Checks if an element is selected
	 */
	isElementSelected(element) {
		return this.stateStore.get('selection.elements').includes(element);
	}

	/**
	 * Gets selection statistics
	 */
	getSelectionStats() {
		const selection = this.getSelectedElements();
		const elements = selection.filter(el => el.isElement());
		const links = selection.filter(el => el.isLink());

		return {
			total: selection.length,
			elements: elements.length,
			links: links.length,
			hasSelection: selection.length > 0,
			selectionType: this.stateStore.get('selection.type'),
			boundingBox: this.stateStore.get('selection.boundingBox')
		};
	}

	/**
	 * Finds elements by selection criteria
	 */
	findElements(criteria) {
		const allCells = this.graphService.graph.getCells();
		
		return allCells.filter(cell => {
			if (criteria.type && criteria.type === 'element' && !cell.isElement()) return false;
			if (criteria.type && criteria.type === 'link' && !cell.isLink()) return false;
			
			if (criteria.attributes) {
				for (const [key, value] of Object.entries(criteria.attributes)) {
					if (cell.get(key) !== value) return false;
				}
			}
			
			if (criteria.bounds && cell.isElement()) {
				const elementBounds = cell.getBBox();
				if (!criteria.bounds.containsRect(elementBounds)) return false;
			}
			
			return true;
		});
	}

	/**
	 * Selects elements matching criteria
	 */
	selectByCriteria(criteria) {
		const matchingElements = this.findElements(criteria);
		this.selectMultiple(matchingElements);

		this.eventBus.emit('selection:by-criteria', {
			criteria,
			elements: matchingElements,
			count: matchingElements.length
		});

		return matchingElements;
	}

	/**
	 * Creates selection box for area selection
	 */
	startAreaSelection(startPoint) {
		this.selectionBox = {
			start: startPoint,
			current: startPoint,
			active: true
		};

		this.eventBus.emit('selection:area-started', { startPoint });
	}

	/**
	 * Updates selection box during area selection
	 */
	updateAreaSelection(currentPoint) {
		if (!this.selectionBox || !this.selectionBox.active) return;

		this.selectionBox.current = currentPoint;
		
		const rect = joint.g.rect(
			Math.min(this.selectionBox.start.x, currentPoint.x),
			Math.min(this.selectionBox.start.y, currentPoint.y),
			Math.abs(currentPoint.x - this.selectionBox.start.x),
			Math.abs(currentPoint.y - this.selectionBox.start.y)
		);

		this.eventBus.emit('selection:area-updated', { rect, currentPoint });
	}

	/**
	 * Completes area selection
	 */
	completeAreaSelection() {
		if (!this.selectionBox || !this.selectionBox.active) return;

		const rect = joint.g.rect(
			Math.min(this.selectionBox.start.x, this.selectionBox.current.x),
			Math.min(this.selectionBox.start.y, this.selectionBox.current.y),
			Math.abs(this.selectionBox.current.x - this.selectionBox.start.x),
			Math.abs(this.selectionBox.current.y - this.selectionBox.start.y)
		);

		if (rect.width > 5 && rect.height > 5) {
			this.selectInArea(rect);
		}

		this.selectionBox = null;
		this.eventBus.emit('selection:area-completed', { rect });
	}

	/**
	 * Cancels area selection
	 */
	cancelAreaSelection() {
		this.selectionBox = null;
		this.eventBus.emit('selection:area-cancelled');
	}

	/**
	 * Enables debug mode
	 */
	setDebugMode(enabled) {
		this.debugMode = enabled;
	}

	/**
	 * Destroys the service
	 */
	destroy() {
		this.clearSelection();
		this.selectionBox = null;
		
		if (this.selectionTimeout) {
			clearTimeout(this.selectionTimeout);
			this.selectionTimeout = null;
		}

		this.initialized = false;
		this.eventBus.emit('selection:service-destroyed');
	}
}