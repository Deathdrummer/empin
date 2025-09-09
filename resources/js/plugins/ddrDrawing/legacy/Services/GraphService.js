/**
 * GraphService - Service for managing JointJS Graph operations and state
 */
export class GraphService {
	constructor(eventBus, stateStore) {
		this.eventBus = eventBus;
		this.stateStore = stateStore;
		this.graph = null;
		this.cellNamespace = null;
		this.initialized = false;
		this.debugMode = false;
		
		this.setupGraphConfiguration();
		this.bindEventHandlers();
	}

	/**
	 * Configures JointJS graph settings and custom cell namespace
	 */
	setupGraphConfiguration() {
		this.cellNamespace = { 
			...joint.shapes,
			...this.createCustomShapes()
		};
	}

	/**
	 * Creates custom JointJS shapes for the editor
	 */
	createCustomShapes() {
		const CustomLink = joint.shapes.standard.Link.extend({
			defaults: joint.util.deepSupplement({
				attrs: {
					line: { 
						targetMarker: { type: 'none' },
						stroke: '#8a8a96',
						strokeWidth: 2
					}
				}
			}, joint.shapes.standard.Link.prototype.defaults)
		});

		const CustomRectangle = joint.shapes.standard.Rectangle.extend({
			defaults: joint.util.deepSupplement({
				attrs: {
					body: {
						fill: '#e9edf0',
						stroke: '#8a8a96',
						strokeWidth: 1
					}
				}
			}, joint.shapes.standard.Rectangle.prototype.defaults)
		});

		return {
			CustomLink,
			CustomRectangle
		};
	}

	/**
	 * Initializes the JointJS graph
	 */
	init() {
		if (this.initialized) {
			console.warn('GraphService: Already initialized');
			return this.graph;
		}

		this.graph = new joint.dia.Graph({}, { 
			cellNamespace: this.cellNamespace 
		});

		this.setupGraphEvents();
		this.initialized = true;

		this.eventBus.emit('graph:initialized', {
			graph: this.graph,
			cellNamespace: this.cellNamespace
		});

		return this.graph;
	}

	/**
	 * Sets up event handlers for graph operations
	 */
	setupGraphEvents() {
		this.graph.on('add', this.handleCellAdded.bind(this));
		this.graph.on('remove', this.handleCellRemoved.bind(this));
		this.graph.on('change:position', this.handleCellMoved.bind(this));
		this.graph.on('change:size', this.handleCellResized.bind(this));
		this.graph.on('change:attrs', this.handleCellStyleChanged.bind(this));
	}

	/**
	 * Binds service to external events
	 */
	bindEventHandlers() {
		this.eventBus.on('graph:clear', () => this.clear());
		this.eventBus.on('graph:load', (event) => this.loadFromJSON(event.data));
		this.eventBus.on('graph:export', () => this.exportToJSON());
		this.eventBus.on('element:create', (event) => this.createElement(event.data));
		this.eventBus.on('link:create', (event) => this.createLink(event.data));
	}

	/**
	 * Handles cell addition to graph
	 */
	handleCellAdded(cell) {
		if (cell.isElement()) {
			this.updateElementsMap(cell, 'add');
			this.eventBus.emit('element:added', { element: cell });
		} else if (cell.isLink()) {
			this.updateLinksMap(cell, 'add');
			this.eventBus.emit('link:added', { link: cell });
		}

		this.updateGraphMetadata();
	}

	/**
	 * Handles cell removal from graph
	 */
	handleCellRemoved(cell) {
		if (cell.isElement()) {
			this.updateElementsMap(cell, 'remove');
			this.eventBus.emit('element:removed', { element: cell });
		} else if (cell.isLink()) {
			this.updateLinksMap(cell, 'remove');
			this.eventBus.emit('link:removed', { link: cell });
		}

		this.updateGraphMetadata();
	}

	/**
	 * Handles cell movement
	 */
	handleCellMoved(cell, newPosition) {
		if (cell.isElement()) {
			this.eventBus.emit('element:moved', { 
				element: cell, 
				position: newPosition 
			});
		}
	}

	/**
	 * Handles cell resizing
	 */
	handleCellResized(cell, newSize) {
		if (cell.isElement()) {
			this.eventBus.emit('element:resized', { 
				element: cell, 
				size: newSize 
			});
		}
	}

	/**
	 * Handles cell style changes
	 */
	handleCellStyleChanged(cell, newAttrs) {
		this.eventBus.emit('cell:style-changed', { 
			cell, 
			attrs: newAttrs 
		});
	}

	/**
	 * Updates elements map in state store
	 */
	updateElementsMap(element, operation) {
		const elementsMap = this.stateStore.get('graph.elements');
		
		if (operation === 'add') {
			elementsMap.set(element.id, {
				id: element.id,
				type: element.get('type'),
				position: element.position(),
				size: element.size(),
				attrs: element.attributes.attrs,
				created: Date.now()
			});
		} else if (operation === 'remove') {
			elementsMap.delete(element.id);
		}

		this.stateStore.set('graph.elements', elementsMap);
	}

	/**
	 * Updates links map in state store
	 */
	updateLinksMap(link, operation) {
		const linksMap = this.stateStore.get('graph.links');
		
		if (operation === 'add') {
			linksMap.set(link.id, {
				id: link.id,
				type: link.get('type'),
				source: link.get('source'),
				target: link.get('target'),
				attrs: link.attributes.attrs,
				created: Date.now()
			});
		} else if (operation === 'remove') {
			linksMap.delete(link.id);
		}

		this.stateStore.set('graph.links', linksMap);
	}

	/**
	 * Updates graph metadata
	 */
	updateGraphMetadata() {
		this.stateStore.set('graph.metadata', {
			...this.stateStore.get('graph.metadata'),
			modified: Date.now(),
			elementCount: this.graph.getElements().length,
			linkCount: this.graph.getLinks().length
		});
	}

	/**
	 * Creates a new element and adds it to the graph
	 */
	createElement(options = {}) {
		const {
			type = 'rectangle',
			position = { x: 100, y: 100 },
			size = { width: 60, height: 40 },
			attrs = {},
			ports = null
		} = options;

		let element;

		switch (type) {
			case 'rectangle':
				element = new this.cellNamespace.CustomRectangle({
					position,
					size,
					attrs: {
						body: { ...attrs }
					}
				});
				break;

			case 'circle':
				element = new joint.shapes.standard.Circle({
					position,
					size,
					attrs: {
						body: { ...attrs }
					}
				});
				break;

			case 'ellipse':
				element = new joint.shapes.standard.Ellipse({
					position,
					size,
					attrs: {
						body: { ...attrs }
					}
				});
				break;

			default:
				element = new this.cellNamespace.CustomRectangle({
					position,
					size,
					attrs: {
						body: { ...attrs }
					}
				});
		}

		if (ports) {
			element.set('ports', ports);
		}

		element.addTo(this.graph);
		return element;
	}

	/**
	 * Creates a new link and adds it to the graph
	 */
	createLink(options = {}) {
		const {
			source,
			target,
			attrs = {},
			router = { name: 'manhattan' },
			connector = { name: 'rounded' },
			labels = []
		} = options;

		const link = new this.cellNamespace.CustomLink({
			source,
			target,
			attrs: {
				line: { ...attrs }
			},
			router,
			connector,
			labels
		});

		link.addTo(this.graph);
		return link;
	}

	/**
	 * Gets element by ID
	 */
	getElementById(id) {
		return this.graph.getCell(id);
	}

	/**
	 * Gets all elements of specified type
	 */
	getElementsByType(type) {
		return this.graph.getElements().filter(element => 
			element.get('type') === type
		);
	}

	/**
	 * Gets elements within specified bounds
	 */
	getElementsInArea(rect) {
		return this.graph.findModelsInArea(rect).filter(cell => 
			cell.isElement()
		);
	}

	/**
	 * Gets links connected to element
	 */
	getConnectedLinks(element) {
		return this.graph.getConnectedLinks(element);
	}

	/**
	 * Gets neighbors of element
	 */
	getNeighbors(element) {
		return this.graph.getNeighbors(element);
	}

	/**
	 * Clones an element with all its properties
	 */
	cloneElement(element, offset = { x: 20, y: 20 }) {
		const clone = element.clone();
		const currentPos = element.position();
		
		clone.position(currentPos.x + offset.x, currentPos.y + offset.y);
		clone.addTo(this.graph);
		
		return clone;
	}

	/**
	 * Groups multiple elements together
	 */
	groupElements(elements, options = {}) {
		const {
			padding = 10,
			label = 'Group'
		} = options;

		const bbox = joint.g.rect();
		elements.forEach(element => {
			bbox.union(element.getBBox());
		});

		const group = new joint.shapes.standard.Rectangle({
			position: { x: bbox.x - padding, y: bbox.y - padding },
			size: { 
				width: bbox.width + padding * 2, 
				height: bbox.height + padding * 2 
			},
			attrs: {
				body: {
					fill: 'transparent',
					stroke: '#666',
					strokeDasharray: '5,5'
				},
				label: {
					text: label,
					fontSize: 12
				}
			}
		});

		group.addTo(this.graph);
		
		elements.forEach(element => {
			group.embed(element);
		});

		return group;
	}

	/**
	 * Exports graph to JSON format
	 */
	exportToJSON() {
		const json = this.graph.toJSON();
		
		this.eventBus.emit('graph:exported', {
			data: json,
			timestamp: Date.now()
		});

		return json;
	}

	/**
	 * Loads graph from JSON data
	 */
	loadFromJSON(jsonData) {
		try {
			this.graph.fromJSON(jsonData, { cellNamespace: this.cellNamespace });
			
			this.eventBus.emit('graph:loaded', {
				data: jsonData,
				timestamp: Date.now()
			});

			this.updateGraphMetadata();
			return true;
		} catch (error) {
			console.error('GraphService: Failed to load from JSON:', error);
			this.eventBus.emit('graph:load-failed', { error });
			return false;
		}
	}

	/**
	 * Validates graph structure and relationships
	 */
	validate() {
		const errors = [];
		const elements = this.graph.getElements();
		const links = this.graph.getLinks();

		links.forEach(link => {
			const source = link.getSourceElement();
			const target = link.getTargetElement();

			if (!source) {
				errors.push(`Link ${link.id} has invalid source`);
			}

			if (!target) {
				errors.push(`Link ${link.id} has invalid target`);
			}

			if (source === target) {
				errors.push(`Link ${link.id} connects element to itself`);
			}
		});

		elements.forEach(element => {
			const bbox = element.getBBox();
			if (bbox.width <= 0 || bbox.height <= 0) {
				errors.push(`Element ${element.id} has invalid dimensions`);
			}
		});

		return {
			isValid: errors.length === 0,
			errors
		};
	}

	/**
	 * Clears the graph
	 */
	clear() {
		this.graph.clear();
		
		this.stateStore.set('graph.elements', new Map());
		this.stateStore.set('graph.links', new Map());
		this.updateGraphMetadata();

		this.eventBus.emit('graph:cleared');
	}

	/**
	 * Gets graph statistics
	 */
	getStats() {
		return {
			elementCount: this.graph.getElements().length,
			linkCount: this.graph.getLinks().length,
			totalCells: this.graph.getCells().length,
			bounds: this.getBounds(),
			hasChanges: this.hasUnsavedChanges(),
			memoryUsage: JSON.stringify(this.exportToJSON()).length
		};
	}

	/**
	 * Gets bounding box of all elements
	 */
	getBounds() {
		const elements = this.graph.getElements();
		if (elements.length === 0) {
			return { x: 0, y: 0, width: 0, height: 0 };
		}

		const bbox = joint.g.rect();
		elements.forEach(element => {
			bbox.union(element.getBBox());
		});

		return bbox;
	}

	/**
	 * Checks if graph has unsaved changes
	 */
	hasUnsavedChanges() {
		const metadata = this.stateStore.get('graph.metadata');
		return metadata.modified > (metadata.lastSaved || 0);
	}

	/**
	 * Marks graph as saved
	 */
	markAsSaved() {
		this.stateStore.set('graph.metadata.lastSaved', Date.now());
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
		if (this.graph) {
			this.graph.clear();
			this.graph = null;
		}
		
		this.cellNamespace = null;
		this.initialized = false;

		this.eventBus.emit('graph:service-destroyed');
	}
}