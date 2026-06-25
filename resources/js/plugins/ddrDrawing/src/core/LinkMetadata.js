export const LineStyles = {
	SOLID: 'solid',
	DASHED: 'dashed',
	DOTTED: 'dotted',
	DASH_DOT: 'dash-dot',
	DASH_DOT_DOT: 'dash-dot-dot',
	LONG_DASH: 'long-dash',
	SHORT_DASH: 'short-dash'
};

export const LineColors = {
	GRAY: '#666666',
	LIGHT_GRAY: '#999999',
	RED: '#ff0000',
	BLUE: '#0066cc',
	GREEN: '#00aa00',
	YELLOW: '#ffaa00'
};

export const ConnectionTypes = {
	SHAPE_TO_SHAPE: 'shape-to-shape',
	SHAPE_TO_CALLOUT: 'shape-to-callout',
	SHAPE_TO_ANNOTATION: 'shape-to-annotation',
	SHAPE_TO_DIMENSION: 'shape-to-dimension',
	SHAPE_TO_PORT: 'shape-to-port',
	PORT_TO_PORT: 'port-to-port',
	CUSTOM: 'custom'
};

export const LineStylePatterns = {
	[LineStyles.SOLID]: '',
	[LineStyles.DASHED]: '10 5',
	[LineStyles.DOTTED]: '2 2',
	[LineStyles.DASH_DOT]: '10 5 2 5',
	[LineStyles.DASH_DOT_DOT]: '10 5 2 5 2 5',
	[LineStyles.LONG_DASH]: '15 5',
	[LineStyles.SHORT_DASH]: '5 3'
};

export class LinkMetadata {
	constructor(link, options = {}) {
		this.link = link;
		
		const defaults = {
			color: LineColors.GRAY,
			lineStyle: LineStyles.SOLID,
			connectionType: ConnectionTypes.SHAPE_TO_SHAPE,
			length: null, // Длина задается вручную
			metadata: {}
		};
		
		this.data = { ...defaults, ...options };
		this.applyToLink();
	}
	
	applyToLink() {
		this.link.prop('metadata', this.data);
		
		this.link.attr('line/stroke', this.data.color);
		this.link.attr('line/strokeDasharray', LineStylePatterns[this.data.lineStyle]);
		this.link.attr('line/targetMarker/fill', this.data.color);
		
		if (this.data.length) {
			this.link.prop('metadata/length', this.data.length);
		}
	}
	
	setColor(color) {
		this.data.color = color;
		this.link.attr('line/stroke', color);
		this.link.attr('line/targetMarker/fill', color);
		this.link.prop('metadata/color', color);
	}
	
	setLineStyle(style) {
		if (!LineStyles[style.toUpperCase()]) {
			console.warn(`Unknown line style: ${style}`);
			return;
		}
		
		this.data.lineStyle = style;
		this.link.attr('line/strokeDasharray', LineStylePatterns[style]);
		this.link.prop('metadata/lineStyle', style);
	}
	
	setConnectionType(type) {
		this.data.connectionType = type;
		this.link.prop('metadata/connectionType', type);
	}
	
	setLength(length) {
		this.data.length = length;
		this.link.prop('metadata/length', length);
	}
	
	getMetadata() {
		return { ...this.data };
	}
	
	static fromLink(link) {
		const existingMetadata = link.prop('metadata');
		if (existingMetadata) {
			return new LinkMetadata(link, existingMetadata);
		}
		return new LinkMetadata(link);
	}
	
	static detectConnectionType(sourceElement, targetElement) {
		if (!sourceElement || !targetElement) {
			return ConnectionTypes.CUSTOM;
		}
		
		const sourceType = sourceElement.get('type');
		const targetType = targetElement.get('type');
		
		if (sourceType?.includes('Callout') || targetType?.includes('Callout')) {
			return ConnectionTypes.SHAPE_TO_CALLOUT;
		}
		
		if (sourceType?.includes('Annotation') || targetType?.includes('Annotation')) {
			return ConnectionTypes.SHAPE_TO_ANNOTATION;
		}
		
		if (sourceType?.includes('Dimension') || targetType?.includes('Dimension')) {
			return ConnectionTypes.SHAPE_TO_DIMENSION;
		}
		
		return ConnectionTypes.SHAPE_TO_SHAPE;
	}
}