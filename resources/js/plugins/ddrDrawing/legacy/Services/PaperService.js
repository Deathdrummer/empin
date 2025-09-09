/**
 * PaperService - Service for managing JointJS Paper operations and interactions
 */
export class PaperService {
	constructor(eventBus, stateStore, graphService) {
		this.eventBus = eventBus;
		this.stateStore = stateStore;
		this.graphService = graphService;
		this.paper = null;
		this.paperElement = null;
		this.initialized = false;
		this.debugMode = false;
		this.interactionState = {
			dragging: false,
			connecting: false,
			selecting: false,
			panning: false
		};
		
		this.bindEventHandlers();
	}

	/**
	 * Initializes the JointJS paper with configuration
	 */
	init(elementSelector = '#ddrCanvas') {
		if (this.initialized) {
			console.warn('PaperService: Already initialized');
			return this.paper;
		}

		this.paperElement = document.querySelector(elementSelector);
		if (!this.paperElement) {
			throw new Error(`PaperService: Element '${elementSelector}' not found`);
		}

		const config = this.createPaperConfiguration();
		this.paper = new joint.dia.Paper(config);
		
		this.setupPaperEvents();
		this.setupPaperProperties();
		this.initialized = true;

		this.eventBus.emit('paper:initialized', {
			paper: this.paper,
			element: this.paperElement
		});

		return this.paper;
	}

	/**
	 * Creates paper configuration object
	 */
	createPaperConfiguration() {
		const canvasState = this.stateStore.get('canvas');
		const graph = this.graphService.graph;
		
		return {
			el: this.paperElement,
			model: graph,
			width: canvasState.width,
			height: canvasState.height,
			gridSize: canvasState.gridSize,
			drawGrid: true,
			background: {
				color: canvasState.background
			},
			cellViewNamespace: this.graphService.cellNamespace,
			linkPinning: true,
			markAvailable: true,
			snapLinks: true,
			defaultLink: () => this.createDefaultLink(),
			validateConnection: (cellViewS, magnetS, cellViewT, magnetT, end, linkView) => {
				return this.validateConnection(cellViewS, magnetS, cellViewT, magnetT, end, linkView);
			},
			validateMagnet: (cellView, magnet) => {
				return this.validateMagnet(cellView, magnet);
			},
			interactive: {
				linkMove: false,
				labelMove: false,
				arrowheadMove: false,
				vertexMove: false,
				vertexAdd: false,
				vertexRemove: false,
				useLinkTools: false
			}
		};
	}

	/**
	 * Sets up paper event handlers
	 */
	setupPaperEvents() {
		// Blank area events
		this.paper.on('blank:pointerdown', this.handleBlankPointerDown.bind(this));
		this.paper.on('blank:pointerup', this.handleBlankPointerUp.bind(this));
		this.paper.on('blank:pointermove', this.handleBlankPointerMove.bind(this));
		this.paper.on('blank:pointerclick', this.handleBlankPointerClick.bind(this));
		this.paper.on('blank:contextmenu', this.handleBlankContextMenu.bind(this));

		// Element events
		this.paper.on('element:pointerdown', this.handleElementPointerDown.bind(this));
		this.paper.on('element:pointerup', this.handleElementPointerUp.bind(this));
		this.paper.on('element:pointermove', this.handleElementPointerMove.bind(this));
		this.paper.on('element:pointerclick', this.handleElementPointerClick.bind(this));
		this.paper.on('element:contextmenu', this.handleElementContextMenu.bind(this));
		this.paper.on('element:mouseenter', this.handleElementMouseEnter.bind(this));
		this.paper.on('element:mouseleave', this.handleElementMouseLeave.bind(this));

		// Link events
		this.paper.on('link:pointerdown', this.handleLinkPointerDown.bind(this));
		this.paper.on('link:pointerup', this.handleLinkPointerUp.bind(this));
		this.paper.on('link:pointermove', this.handleLinkPointerMove.bind(this));
		this.paper.on('link:pointerclick', this.handleLinkPointerClick.bind(this));
		this.paper.on('link:contextmenu', this.handleLinkContextMenu.bind(this));
		this.paper.on('link:connect', this.handleLinkConnect.bind(this));
		this.paper.on('link:disconnect', this.handleLinkDisconnect.bind(this));

		// Cell events
		this.paper.on('cell:highlight', this.handleCellHighlight.bind(this));
		this.paper.on('cell:unhighlight', this.handleCellUnhighlight.bind(this));
	}

	/**
	 * Sets up paper properties and styling
	 */
	setupPaperProperties() {
		this.paperElement.style.position = 'relative';
		this.paperElement.style.overflow = 'hidden';
		this.paperElement.style.userSelect = 'none';
	}

	/**
	 * Binds service to external events
	 */
	bindEventHandlers() {
		this.eventBus.on('canvas:resize', (event) => this.resize(event.data.width, event.data.height));
		this.eventBus.on('canvas:zoom', (event) => this.setZoom(event.data.zoom));
		this.eventBus.on('canvas:pan', (event) => this.setPan(event.data.x, event.data.y));
		this.eventBus.on('canvas:fit-content', () => this.fitContent());
		this.eventBus.on('canvas:reset-view', () => this.resetView());
		this.eventBus.on('paper:set-interactive', (event) => this.setInteractive(event.data));
	}

	/**
	 * Creates default link configuration
	 */
	createDefaultLink() {
		const connectionState = this.stateStore.get('connections');
		
		return new this.graphService.cellNamespace.CustomLink({
			attrs: {
				line: {
					stroke: '#8a8a96',
					strokeWidth: 2,
					targetMarker: { type: 'none' }
				}
			},
			router: { name: connectionState.router },
			connector: { name: connectionState.connector }
		});
	}

	/**
	 * Validates connection between elements
	 */
	validateConnection(cellViewS, magnetS, cellViewT, magnetT, end, linkView) {
		if (cellViewS === cellViewT) {
			return false;
		}

		if (!magnetS || !magnetT) {
			return false;
		}

		this.eventBus.emit('connection:validate', {
			source: { cellView: cellViewS, magnet: magnetS },
			target: { cellView: cellViewT, magnet: magnetT },
			end,
			linkView
		});

		return true;
	}

	/**
	 * Validates magnet for connections
	 */
	validateMagnet(cellView, magnet) {
		this.eventBus.emit('magnet:validate', { cellView, magnet });
		return magnet.getAttribute('magnet') !== 'passive';
	}

	/**
	 * Handle blank area pointer down
	 */
	handleBlankPointerDown(event, x, y) {
		this.interactionState.selecting = true;
		
		this.eventBus.emit('paper:blank-pointerdown', {
			coordinates: { x, y },
			originalEvent: event
		});
	}

	/**
	 * Handle blank area pointer up
	 */
	handleBlankPointerUp(event, x, y) {
		this.interactionState.selecting = false;
		
		this.eventBus.emit('paper:blank-pointerup', {
			coordinates: { x, y },
			originalEvent: event
		});
	}

	/**
	 * Handle blank area pointer move
	 */
	handleBlankPointerMove(event, x, y) {
		this.eventBus.emit('paper:blank-pointermove', {
			coordinates: { x, y },
			originalEvent: event
		});
	}

	/**
	 * Handle blank area pointer click
	 */
	handleBlankPointerClick(event, x, y) {
		this.eventBus.emit('paper:blank-click', {
			coordinates: { x, y },
			originalEvent: event
		});
	}

	/**
	 * Handle blank area context menu
	 */
	handleBlankContextMenu(event, x, y) {
		this.eventBus.emit('paper:blank-contextmenu', {
			coordinates: { x, y },
			originalEvent: event
		});
	}

	/**
	 * Handle element pointer down
	 */
	handleElementPointerDown(elementView, event, x, y) {
		this.interactionState.dragging = true;
		
		this.eventBus.emit('paper:element-pointerdown', {
			element: elementView.model,
			elementView,
			coordinates: { x, y },
			originalEvent: event
		});
	}

	/**
	 * Handle element pointer up
	 */
	handleElementPointerUp(elementView, event, x, y) {
		this.interactionState.dragging = false;
		
		this.eventBus.emit('paper:element-pointerup', {
			element: elementView.model,
			elementView,
			coordinates: { x, y },
			originalEvent: event
		});
	}

	/**
	 * Handle element pointer move
	 */
	handleElementPointerMove(elementView, event, x, y) {
		this.eventBus.emit('paper:element-pointermove', {
			element: elementView.model,
			elementView,
			coordinates: { x, y },
			originalEvent: event
		});
	}

	/**
	 * Handle element pointer click
	 */
	handleElementPointerClick(elementView, event, x, y) {
		this.eventBus.emit('paper:element-click', {
			element: elementView.model,
			elementView,
			coordinates: { x, y },
			originalEvent: event
		});
	}

	/**
	 * Handle element context menu
	 */
	handleElementContextMenu(elementView, event, x, y) {
		this.eventBus.emit('paper:element-contextmenu', {
			element: elementView.model,
			elementView,
			coordinates: { x, y },
			originalEvent: event
		});
	}

	/**
	 * Handle element mouse enter
	 */
	handleElementMouseEnter(elementView, event) {
		this.eventBus.emit('paper:element-mouseenter', {
			element: elementView.model,
			elementView,
			originalEvent: event
		});
	}

	/**
	 * Handle element mouse leave
	 */
	handleElementMouseLeave(elementView, event) {
		this.eventBus.emit('paper:element-mouseleave', {
			element: elementView.model,
			elementView,
			originalEvent: event
		});
	}

	/**
	 * Handle link pointer down
	 */
	handleLinkPointerDown(linkView, event, x, y) {
		this.eventBus.emit('paper:link-pointerdown', {
			link: linkView.model,
			linkView,
			coordinates: { x, y },
			originalEvent: event
		});
	}

	/**
	 * Handle link pointer up
	 */
	handleLinkPointerUp(linkView, event, x, y) {
		this.eventBus.emit('paper:link-pointerup', {
			link: linkView.model,
			linkView,
			coordinates: { x, y },
			originalEvent: event
		});
	}

	/**
	 * Handle link pointer move
	 */
	handleLinkPointerMove(linkView, event, x, y) {
		this.eventBus.emit('paper:link-pointermove', {
			link: linkView.model,
			linkView,
			coordinates: { x, y },
			originalEvent: event
		});
	}

	/**
	 * Handle link pointer click
	 */
	handleLinkPointerClick(linkView, event, x, y) {
		this.eventBus.emit('paper:link-click', {
			link: linkView.model,
			linkView,
			coordinates: { x, y },
			originalEvent: event
		});
	}

	/**
	 * Handle link context menu
	 */
	handleLinkContextMenu(linkView, event, x, y) {
		this.eventBus.emit('paper:link-contextmenu', {
			link: linkView.model,
			linkView,
			coordinates: { x, y },
			originalEvent: event
		});
	}

	/**
	 * Handle link connect
	 */
	handleLinkConnect(linkView, event, elementViewConnectedTo, magnet, arrowhead) {
		this.interactionState.connecting = false;
		
		this.eventBus.emit('paper:link-connect', {
			link: linkView.model,
			linkView,
			connectedTo: elementViewConnectedTo.model,
			magnet,
			arrowhead
		});
	}

	/**
	 * Handle link disconnect
	 */
	handleLinkDisconnect(linkView, event, elementViewDisconnectedFrom, magnet, arrowhead) {
		this.eventBus.emit('paper:link-disconnect', {
			link: linkView.model,
			linkView,
			disconnectedFrom: elementViewDisconnectedFrom.model,
			magnet,
			arrowhead
		});
	}

	/**
	 * Handle cell highlight
	 */
	handleCellHighlight(cellView, event) {
		this.eventBus.emit('paper:cell-highlight', {
			cell: cellView.model,
			cellView
		});
	}

	/**
	 * Handle cell unhighlight
	 */
	handleCellUnhighlight(cellView, event) {
		this.eventBus.emit('paper:cell-unhighlight', {
			cell: cellView.model,
			cellView
		});
	}

	/**
	 * Resizes the paper
	 */
	resize(width, height) {
		if (!this.paper) return;

		this.paper.setDimensions(width, height);
		this.stateStore.setBatch({
			'canvas.width': width,
			'canvas.height': height
		});

		this.eventBus.emit('paper:resized', { width, height });
	}

	/**
	 * Sets paper zoom level
	 */
	setZoom(zoom, point = null) {
		if (!this.paper) return;

		const clampedZoom = Math.max(0.1, Math.min(5, zoom));
		
		if (point) {
			this.paper.scale(clampedZoom, clampedZoom, point.x, point.y);
		} else {
			this.paper.scale(clampedZoom, clampedZoom);
		}

		this.stateStore.set('canvas.zoom', clampedZoom);
		this.eventBus.emit('paper:zoomed', { zoom: clampedZoom, point });
	}

	/**
	 * Sets paper pan position
	 */
	setPan(x, y) {
		if (!this.paper) return;

		this.paper.translate(x, y);
		this.stateStore.setBatch({
			'canvas.pan.x': x,
			'canvas.pan.y': y
		});

		this.eventBus.emit('paper:panned', { x, y });
	}

	/**
	 * Fits content to paper view
	 */
	fitContent(padding = 20) {
		if (!this.paper) return;

		this.paper.scaleContentToFit({
			padding,
			preserveAspectRatio: true,
			minScale: 0.1,
			maxScale: 2
		});

		const scale = this.paper.scale();
		this.stateStore.set('canvas.zoom', scale.sx);

		this.eventBus.emit('paper:content-fitted', { padding, scale });
	}

	/**
	 * Resets paper view to default
	 */
	resetView() {
		if (!this.paper) return;

		this.paper.scale(1, 1);
		this.paper.translate(0, 0);
		
		this.stateStore.setBatch({
			'canvas.zoom': 1,
			'canvas.pan.x': 0,
			'canvas.pan.y': 0
		});

		this.eventBus.emit('paper:view-reset');
	}

	/**
	 * Sets paper interactive mode
	 */
	setInteractive(options) {
		if (!this.paper) return;

		this.paper.setInteractivity(options);
		this.eventBus.emit('paper:interactive-changed', { options });
	}

	/**
	 * Gets element view by model ID
	 */
	getElementView(elementId) {
		if (!this.paper) return null;
		
		const element = this.graphService.getElementById(elementId);
		return element ? this.paper.findViewByModel(element) : null;
	}

	/**
	 * Gets all element views
	 */
	getElementViews() {
		if (!this.paper) return [];
		
		return this.graphService.graph.getElements().map(element => 
			this.paper.findViewByModel(element)
		).filter(view => view);
	}

	/**
	 * Converts local coordinates to paper coordinates
	 */
	localToPaperCoordinates(x, y) {
		if (!this.paper) return { x, y };
		
		return this.paper.localToPaperCoordinates(x, y);
	}

	/**
	 * Converts paper coordinates to local coordinates
	 */
	paperToLocalCoordinates(x, y) {
		if (!this.paper) return { x, y };
		
		return this.paper.paperToLocalCoordinates(x, y);
	}

	/**
	 * Gets paper metrics and statistics
	 */
	getStats() {
		if (!this.paper) return null;

		const scale = this.paper.scale();
		const translate = this.paper.translate();
		
		return {
			dimensions: {
				width: this.paper.options.width,
				height: this.paper.options.height
			},
			transform: {
				scale: scale.sx,
				translate: { x: translate.tx, y: translate.ty }
			},
			interactionState: { ...this.interactionState },
			viewCount: this.getElementViews().length
		};
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
		if (this.paper) {
			this.paper.remove();
			this.paper = null;
		}
		
		this.paperElement = null;
		this.initialized = false;
		this.interactionState = {
			dragging: false,
			connecting: false,
			selecting: false,
			panning: false
		};

		this.eventBus.emit('paper:service-destroyed');
	}
}