import { BaseCommand } from './BaseCommand.js';

/**
 * MoveCommand - Command for moving elements to new positions
 */
export class MoveCommand extends BaseCommand {
	constructor(validationService, elements, newPositions, options = {}) {
		const elementCount = Array.isArray(elements) ? elements.length : 1;
		const description = elementCount === 1 
			? 'Move element' 
			: `Move ${elementCount} elements`;
		
		super(description);
		
		this.validationService = validationService;
		this.elements = Array.isArray(elements) ? elements : [elements];
		this.newPositions = Array.isArray(newPositions) ? newPositions : [newPositions];
		this.originalPositions = [];
		this.snapToGrid = options.snapToGrid !== false;
		this.gridSize = options.gridSize || 10;
		this.validateBounds = options.validateBounds !== false;
		this.updateConnections = options.updateConnections !== false;
	}

	/**
	 * Validates command parameters before execution
	 */
	validateParameters() {
		const errors = [];

		if (!Array.isArray(this.elements) || this.elements.length === 0) {
			errors.push('No elements specified for movement');
		}

		if (!Array.isArray(this.newPositions) || this.newPositions.length === 0) {
			errors.push('No new positions specified');
		}

		if (this.elements.length !== this.newPositions.length) {
			errors.push('Number of elements must match number of new positions');
		}

		this.elements.forEach((element, index) => {
			if (!element || !element.isElement()) {
				errors.push(`Item at index ${index} is not a valid element`);
			}
		});

		this.newPositions.forEach((position, index) => {
			if (!position || typeof position.x !== 'number' || typeof position.y !== 'number') {
				errors.push(`Position at index ${index} must contain valid x and y coordinates`);
			}
		});

		return {
			valid: errors.length === 0,
			errors
		};
	}

	/**
	 * Calculates grid-snapped position
	 */
	getSnappedPosition(position) {
		if (!this.snapToGrid) {
			return position;
		}

		return {
			x: Math.round(position.x / this.gridSize) * this.gridSize,
			y: Math.round(position.y / this.gridSize) * this.gridSize
		};
	}

	/**
	 * Calculates final positions for all elements
	 */
	calculateFinalPositions() {
		return this.newPositions.map(position => this.getSnappedPosition(position));
	}

	/**
	 * Stores original positions for undo functionality
	 */
	storeOriginalPositions() {
		this.originalPositions = this.elements.map(element => ({
			x: element.position().x,
			y: element.position().y
		}));
	}

	/**
	 * Validates movement with business rules
	 */
	isValid() {
		const paramValidation = this.validateParameters();
		if (!paramValidation.valid) {
			return false;
		}

		if (!this.validateBounds) {
			return true;
		}

		const finalPositions = this.calculateFinalPositions();
		
		return this.elements.every((element, index) => {
			const validation = this.validationService.validateElementMove(
				element,
				finalPositions[index]
			);
			return validation.valid;
		});
	}

	/**
	 * Calculates movement delta for relative positioning
	 */
	calculateMovementDelta() {
		if (this.elements.length === 0 || this.newPositions.length === 0) {
			return { x: 0, y: 0 };
		}

		const firstElement = this.elements[0];
		const firstNewPosition = this.getSnappedPosition(this.newPositions[0]);
		const firstOriginalPosition = firstElement.position();

		return {
			x: firstNewPosition.x - firstOriginalPosition.x,
			y: firstNewPosition.y - firstOriginalPosition.y
		};
	}

	/**
	 * Executes the move command
	 */
	execute() {
		try {
			if (!this.isValid()) {
				return false;
			}

			this.storeOriginalPositions();
			const finalPositions = this.calculateFinalPositions();
			const movementDelta = this.calculateMovementDelta();

			this.elements.forEach((element, index) => {
				element.position(finalPositions[index]);
			});

			this.setMetadata('elementIds', this.elements.map(el => el.id));
			this.setMetadata('originalPositions', this.originalPositions);
			this.setMetadata('finalPositions', finalPositions);
			this.setMetadata('movementDelta', movementDelta);
			this.markAsExecuted();

			return true;

		} catch (error) {
			console.error('MoveCommand: Execution failed:', error);
			return false;
		}
	}

	/**
	 * Undoes the move command
	 */
	undo() {
		try {
			if (!this.executed || this.originalPositions.length === 0) {
				return false;
			}

			this.elements.forEach((element, index) => {
				if (this.originalPositions[index]) {
					element.position(this.originalPositions[index]);
				}
			});

			this.markAsNotExecuted();
			return true;

		} catch (error) {
			console.error('MoveCommand: Undo failed:', error);
			return false;
		}
	}

	/**
	 * Redoes the move command
	 */
	redo() {
		if (this.executed) {
			return true;
		}

		const finalPositions = this.getMetadata('finalPositions');
		if (!finalPositions) {
			return this.execute();
		}

		try {
			this.elements.forEach((element, index) => {
				if (finalPositions[index]) {
					element.position(finalPositions[index]);
				}
			});

			this.markAsExecuted();
			return true;

		} catch (error) {
			console.error('MoveCommand: Redo failed:', error);
			return false;
		}
	}

	/**
	 * Gets entities affected by this command
	 */
	getAffectedEntities() {
		return this.elements.map(element => element.id);
	}

	/**
	 * Checks if command affects specific entities
	 */
	affectsEntities(entityIds) {
		const affectedIds = this.getAffectedEntities();
		return affectedIds.some(id => entityIds.includes(id));
	}

	/**
	 * Gets movement information
	 */
	getMovementInfo() {
		return {
			elements: this.elements,
			originalPositions: this.originalPositions,
			finalPositions: this.getMetadata('finalPositions'),
			movementDelta: this.getMetadata('movementDelta')
		};
	}

	/**
	 * Checks if this command can be merged with another move command
	 */
	canMergeWith(otherCommand) {
		if (!(otherCommand instanceof MoveCommand)) {
			return false;
		}

		const timeDiff = Math.abs(this.timestamp - otherCommand.timestamp);
		const maxMergeTime = 500; // 500ms for move commands

		if (timeDiff > maxMergeTime) {
			return false;
		}

		const thisElementIds = new Set(this.elements.map(el => el.id));
		const otherElementIds = new Set(otherCommand.elements.map(el => el.id));
		
		const hasCommonElements = [...thisElementIds].some(id => otherElementIds.has(id));
		return hasCommonElements;
	}

	/**
	 * Merges this command with another compatible move command
	 */
	mergeWith(otherCommand) {
		if (!this.canMergeWith(otherCommand)) {
			return null;
		}

		const allElements = [...this.elements];
		const allNewPositions = [...this.newPositions];

		otherCommand.elements.forEach((element, index) => {
			const existingIndex = allElements.findIndex(el => el.id === element.id);
			
			if (existingIndex >= 0) {
				allNewPositions[existingIndex] = otherCommand.newPositions[index];
			} else {
				allElements.push(element);
				allNewPositions.push(otherCommand.newPositions[index]);
			}
		});

		const mergedCommand = new MoveCommand(
			this.validationService,
			allElements,
			allNewPositions,
			{
				snapToGrid: this.snapToGrid,
				gridSize: this.gridSize,
				validateBounds: this.validateBounds,
				updateConnections: this.updateConnections
			}
		);

		mergedCommand.setDescription(`Move ${allElements.length} elements`);
		mergedCommand.setGroupId(this.groupId || this.id);

		return mergedCommand;
	}

	/**
	 * Creates a relative move command based on delta
	 */
	createRelativeMove(deltaX, deltaY) {
		const newPositions = this.elements.map(element => {
			const currentPos = element.position();
			return {
				x: currentPos.x + deltaX,
				y: currentPos.y + deltaY
			};
		});

		const relativeCommand = new MoveCommand(
			this.validationService,
			this.elements,
			newPositions,
			{
				snapToGrid: this.snapToGrid,
				gridSize: this.gridSize,
				validateBounds: this.validateBounds,
				updateConnections: this.updateConnections
			}
		);

		relativeCommand.setDescription(`Move ${this.elements.length} elements by offset`);
		return relativeCommand;
	}

	/**
	 * Gets estimated execution time
	 */
	getEstimatedExecutionTime() {
		return this.elements.length * 10; // 10ms per element
	}

	/**
	 * Serializes command-specific data
	 */
	toJSON() {
		const baseData = super.toJSON();
		
		return {
			...baseData,
			elementIds: this.elements.map(el => el.id),
			newPositions: this.newPositions,
			originalPositions: this.originalPositions,
			snapToGrid: this.snapToGrid,
			gridSize: this.gridSize,
			validateBounds: this.validateBounds,
			updateConnections: this.updateConnections
		};
	}

	/**
	 * Restores command from JSON data
	 */
	static fromJSON(data, validationService, graphService) {
		const elements = data.elementIds.map(id => graphService.getElementById(id)).filter(Boolean);
		
		const command = new MoveCommand(
			validationService,
			elements,
			data.newPositions,
			{
				snapToGrid: data.snapToGrid,
				gridSize: data.gridSize,
				validateBounds: data.validateBounds,
				updateConnections: data.updateConnections
			}
		);

		command.id = data.id;
		command.description = data.description;
		command.executed = data.executed;
		command.timestamp = data.timestamp;
		command.groupId = data.groupId;
		command.metadata = data.metadata || {};
		command.originalPositions = data.originalPositions || [];

		return command;
	}
}

/**
 * MoveMultipleCommand - Command for moving multiple separate groups of elements
 */
export class MoveMultipleCommand extends BaseCommand {
	constructor(moveCommands = []) {
		super('Move multiple groups');
		this.moveCommands = moveCommands;
	}

	/**
	 * Validates all sub-commands
	 */
	isValid() {
		return this.moveCommands.every(command => command.isValid());
	}

	/**
	 * Executes all move commands
	 */
	execute() {
		try {
			for (const command of this.moveCommands) {
				const result = command.execute();
				if (result === false) {
					this.undoExecutedCommands();
					return false;
				}
			}

			this.markAsExecuted();
			return true;

		} catch (error) {
			this.undoExecutedCommands();
			console.error('MoveMultipleCommand: Execution failed:', error);
			return false;
		}
	}

	/**
	 * Undoes all move commands in reverse order
	 */
	undo() {
		try {
			for (let i = this.moveCommands.length - 1; i >= 0; i--) {
				const command = this.moveCommands[i];
				if (command.executed) {
					command.undo();
				}
			}

			this.markAsNotExecuted();
			return true;

		} catch (error) {
			console.error('MoveMultipleCommand: Undo failed:', error);
			return false;
		}
	}

	/**
	 * Undoes only the successfully executed commands
	 */
	undoExecutedCommands() {
		for (let i = this.moveCommands.length - 1; i >= 0; i--) {
			const command = this.moveCommands[i];
			if (command.executed) {
				try {
					command.undo();
				} catch (error) {
					console.error('MoveMultipleCommand: Error undoing command:', error);
				}
			}
		}
	}

	/**
	 * Gets entities affected by all sub-commands
	 */
	getAffectedEntities() {
		const entities = new Set();
		this.moveCommands.forEach(command => {
			command.getAffectedEntities().forEach(id => entities.add(id));
		});
		return Array.from(entities);
	}

	/**
	 * Gets estimated execution time for all commands
	 */
	getEstimatedExecutionTime() {
		return this.moveCommands.reduce((total, cmd) => total + cmd.getEstimatedExecutionTime(), 0);
	}
}

/**
 * AlignCommand - Command for aligning multiple elements
 */
export class AlignCommand extends BaseCommand {
	constructor(validationService, elements, alignmentType, options = {}) {
		super(`Align ${elements.length} elements ${alignmentType}`);
		
		this.validationService = validationService;
		this.elements = elements;
		this.alignmentType = alignmentType; // 'left', 'right', 'top', 'bottom', 'center-horizontal', 'center-vertical'
		this.originalPositions = [];
		this.referenceElement = options.referenceElement || null;
		this.snapToGrid = options.snapToGrid !== false;
		this.gridSize = options.gridSize || 10;
	}

	/**
	 * Calculates alignment positions based on type
	 */
	calculateAlignmentPositions() {
		if (this.elements.length < 2) {
			return this.elements.map(el => el.position());
		}

		let referenceValue;
		const referenceBounds = this.referenceElement 
			? this.referenceElement.getBBox()
			: this.calculateGroupBounds();

		switch (this.alignmentType) {
			case 'left':
				referenceValue = referenceBounds.x;
				return this.elements.map(el => ({
					x: referenceValue,
					y: el.position().y
				}));

			case 'right':
				referenceValue = referenceBounds.x + referenceBounds.width;
				return this.elements.map(el => {
					const elBounds = el.getBBox();
					return {
						x: referenceValue - elBounds.width,
						y: el.position().y
					};
				});

			case 'top':
				referenceValue = referenceBounds.y;
				return this.elements.map(el => ({
					x: el.position().x,
					y: referenceValue
				}));

			case 'bottom':
				referenceValue = referenceBounds.y + referenceBounds.height;
				return this.elements.map(el => {
					const elBounds = el.getBBox();
					return {
						x: el.position().x,
						y: referenceValue - elBounds.height
					};
				});

			case 'center-horizontal':
				referenceValue = referenceBounds.x + referenceBounds.width / 2;
				return this.elements.map(el => {
					const elBounds = el.getBBox();
					return {
						x: referenceValue - elBounds.width / 2,
						y: el.position().y
					};
				});

			case 'center-vertical':
				referenceValue = referenceBounds.y + referenceBounds.height / 2;
				return this.elements.map(el => {
					const elBounds = el.getBBox();
					return {
						x: el.position().x,
						y: referenceValue - elBounds.height / 2
					};
				});

			default:
				return this.elements.map(el => el.position());
		}
	}

	/**
	 * Calculates bounding box for all elements
	 */
	calculateGroupBounds() {
		const bbox = joint.g.rect();
		this.elements.forEach(element => {
			bbox.union(element.getBBox());
		});
		return bbox;
	}

	/**
	 * Executes the alignment command
	 */
	execute() {
		try {
			this.originalPositions = this.elements.map(el => el.position());
			const alignedPositions = this.calculateAlignmentPositions();

			this.elements.forEach((element, index) => {
				const newPosition = this.snapToGrid 
					? this.getSnappedPosition(alignedPositions[index])
					: alignedPositions[index];
				element.position(newPosition);
			});

			this.setMetadata('elementIds', this.elements.map(el => el.id));
			this.setMetadata('originalPositions', this.originalPositions);
			this.setMetadata('alignedPositions', alignedPositions);
			this.markAsExecuted();

			return true;

		} catch (error) {
			console.error('AlignCommand: Execution failed:', error);
			return false;
		}
	}

	/**
	 * Undoes the alignment command
	 */
	undo() {
		try {
			if (!this.executed || this.originalPositions.length === 0) {
				return false;
			}

			this.elements.forEach((element, index) => {
				if (this.originalPositions[index]) {
					element.position(this.originalPositions[index]);
				}
			});

			this.markAsNotExecuted();
			return true;

		} catch (error) {
			console.error('AlignCommand: Undo failed:', error);
			return false;
		}
	}

	/**
	 * Calculates grid-snapped position
	 */
	getSnappedPosition(position) {
		if (!this.snapToGrid) {
			return position;
		}

		return {
			x: Math.round(position.x / this.gridSize) * this.gridSize,
			y: Math.round(position.y / this.gridSize) * this.gridSize
		};
	}

	/**
	 * Gets entities affected by this command
	 */
	getAffectedEntities() {
		return this.elements.map(element => element.id);
	}

	/**
	 * Gets estimated execution time
	 */
	getEstimatedExecutionTime() {
		return this.elements.length * 15; // 15ms per element for alignment
	}
}