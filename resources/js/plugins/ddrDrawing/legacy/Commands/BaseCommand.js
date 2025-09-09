 
/**
 * BaseCommand - Abstract base class for all commands in the system
 * Implements the Command pattern with undo/redo functionality
 */
export class BaseCommand {
	constructor(description = 'Unknown command') {
		this.description = description;
		this.executed = false;
		this.timestamp = Date.now();
		this.id = this.generateCommandId();
		this.groupId = null;
		this.stateBefore = null;
		this.stateAfter = null;
		this.metadata = {};
	}

	/**
	 * Generates a unique ID for the command
	 */
	generateCommandId() {
		return `cmd_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
	}

	/**
	 * Executes the command - must be implemented by subclasses
	 * @returns {boolean|*} Success status or result
	 */
	execute() {
		throw new Error('BaseCommand: execute() method must be implemented by subclass');
	}

	/**
	 * Undoes the command - must be implemented by subclasses
	 * @returns {boolean} Success status
	 */
	undo() {
		throw new Error('BaseCommand: undo() method must be implemented by subclass');
	}

	/**
	 * Redoes the command - default implementation calls execute()
	 * @returns {boolean|*} Success status or result
	 */
	redo() {
		return this.execute();
	}

	/**
	 * Validates if the command can be executed
	 * @returns {boolean} True if command is valid
	 */
	isValid() {
		return true;
	}

	/**
	 * Checks if the command can be undone
	 * @returns {boolean} True if command can be undone
	 */
	canUndo() {
		return this.executed;
	}

	/**
	 * Checks if the command can be redone
	 * @returns {boolean} True if command can be redone
	 */
	canRedo() {
		return !this.executed;
	}

	/**
	 * Gets the command description for display
	 * @returns {string} Command description
	 */
	getDescription() {
		return this.description;
	}

	/**
	 * Sets the command description
	 * @param {string} description - New description
	 */
	setDescription(description) {
		this.description = description;
	}

	/**
	 * Sets the group ID for command batching
	 * @param {string} groupId - Group identifier
	 */
	setGroupId(groupId) {
		this.groupId = groupId;
	}

	/**
	 * Sets state snapshots for the command
	 * @param {Object} stateBefore - State before execution
	 * @param {Object} stateAfter - State after execution
	 */
	setStateSnapshots(stateBefore, stateAfter) {
		this.stateBefore = stateBefore;
		this.stateAfter = stateAfter;
	}

	/**
	 * Gets the state before command execution
	 * @returns {Object|null} State snapshot
	 */
	getStateBefore() {
		return this.stateBefore;
	}

	/**
	 * Gets the state after command execution
	 * @returns {Object|null} State snapshot
	 */
	getStateAfter() {
		return this.stateAfter;
	}

	/**
	 * Sets metadata for the command
	 * @param {string} key - Metadata key
	 * @param {*} value - Metadata value
	 */
	setMetadata(key, value) {
		this.metadata[key] = value;
	}

	/**
	 * Gets metadata value by key
	 * @param {string} key - Metadata key
	 * @returns {*} Metadata value
	 */
	getMetadata(key) {
		return this.metadata[key];
	}

	/**
	 * Gets all metadata
	 * @returns {Object} Metadata object
	 */
	getAllMetadata() {
		return { ...this.metadata };
	}

	/**
	 * Marks the command as executed
	 */
	markAsExecuted() {
		this.executed = true;
	}

	/**
	 * Marks the command as not executed
	 */
	markAsNotExecuted() {
		this.executed = false;
	}

	/**
	 * Checks if command affects specific entities
	 * @param {Array} entityIds - IDs to check
	 * @returns {boolean} True if command affects any of the entities
	 */
	affectsEntities(entityIds) {
		return false; // Override in subclasses
	}

	/**
	 * Gets entities affected by this command
	 * @returns {Array} Array of affected entity IDs
	 */
	getAffectedEntities() {
		return []; // Override in subclasses
	}

	/**
	 * Estimates the memory usage of this command
	 * @returns {number} Estimated memory usage in bytes
	 */
	getMemoryUsage() {
		const baseSize = JSON.stringify({
			description: this.description,
			executed: this.executed,
			timestamp: this.timestamp,
			id: this.id,
			groupId: this.groupId,
			metadata: this.metadata
		}).length;

		const stateSize = this.stateBefore ? JSON.stringify(this.stateBefore).length : 0;
		const stateAfterSize = this.stateAfter ? JSON.stringify(this.stateAfter).length : 0;

		return baseSize + stateSize + stateAfterSize;
	}

	/**
	 * Creates a summary of the command for logging/debugging
	 * @returns {Object} Command summary
	 */
	getSummary() {
		return {
			id: this.id,
			description: this.description,
			executed: this.executed,
			timestamp: this.timestamp,
			groupId: this.groupId,
			canUndo: this.canUndo(),
			canRedo: this.canRedo(),
			memoryUsage: this.getMemoryUsage(),
			affectedEntities: this.getAffectedEntities()
		};
	}

	/**
	 * Serializes the command to JSON
	 * @returns {Object} Serialized command data
	 */
	toJSON() {
		return {
			type: this.constructor.name,
			id: this.id,
			description: this.description,
			executed: this.executed,
			timestamp: this.timestamp,
			groupId: this.groupId,
			metadata: this.metadata,
			stateBefore: this.stateBefore,
			stateAfter: this.stateAfter
		};
	}

	/**
	 * Creates a command from JSON data
	 * @param {Object} data - Serialized command data
	 * @returns {BaseCommand} Restored command instance
	 */
	static fromJSON(data) {
		const command = new this();
		command.id = data.id;
		command.description = data.description;
		command.executed = data.executed;
		command.timestamp = data.timestamp;
		command.groupId = data.groupId;
		command.metadata = data.metadata || {};
		command.stateBefore = data.stateBefore;
		command.stateAfter = data.stateAfter;
		return command;
	}

	/**
	 * Clones the command with a new ID
	 * @returns {BaseCommand} Cloned command
	 */
	clone() {
		const cloned = new this.constructor();
		cloned.description = this.description;
		cloned.metadata = { ...this.metadata };
		cloned.groupId = this.groupId;
		return cloned;
	}

	/**
	 * Merges this command with another compatible command
	 * @param {BaseCommand} otherCommand - Command to merge with
	 * @returns {BaseCommand|null} Merged command or null if incompatible
	 */
	mergeWith(otherCommand) {
		return null; // Override in subclasses that support merging
	}

	/**
	 * Checks if this command can be merged with another
	 * @param {BaseCommand} otherCommand - Command to check compatibility with
	 * @returns {boolean} True if commands can be merged
	 */
	canMergeWith(otherCommand) {
		return false; // Override in subclasses that support merging
	}

	/**
	 * Gets the command priority for execution order
	 * @returns {number} Priority value (higher = more priority)
	 */
	getPriority() {
		return 0; // Override in subclasses if needed
	}

	/**
	 * Checks if the command should be saved in history
	 * @returns {boolean} True if command should be saved
	 */
	shouldSaveInHistory() {
		return true; // Override in subclasses for temporary commands
	}

	/**
	 * Gets dependencies that must be executed before this command
	 * @returns {Array<string>} Array of command IDs this command depends on
	 */
	getDependencies() {
		return []; // Override in subclasses with dependencies
	}

	/**
	 * Performs cleanup when command is removed from history
	 */
	cleanup() {
		this.stateBefore = null;
		this.stateAfter = null;
		this.metadata = {};
	}

	/**
	 * Validates command parameters before execution
	 * @returns {Object} Validation result with valid flag and errors
	 */
	validateParameters() {
		return {
			valid: true,
			errors: []
		};
	}

	/**
	 * Gets estimated execution time in milliseconds
	 * @returns {number} Estimated execution time
	 */
	getEstimatedExecutionTime() {
		return 0; // Override in subclasses if needed
	}

	/**
	 * Checks if command execution should be logged
	 * @returns {boolean} True if execution should be logged
	 */
	shouldLog() {
		return true;
	}

	/**
	 * Gets log level for this command
	 * @returns {string} Log level ('debug', 'info', 'warn', 'error')
	 */
	getLogLevel() {
		return 'info';
	}

	/**
	 * Creates a string representation of the command
	 * @returns {string} String representation
	 */
	toString() {
		return `${this.constructor.name}[${this.id}]: ${this.description} (executed: ${this.executed})`;
	}
}

/**
 * CompositeCommand - Command that contains multiple sub-commands
 */
export class CompositeCommand extends BaseCommand {
	constructor(description = 'Composite command') {
		super(description);
		this.commands = [];
	}

	/**
	 * Adds a command to the composite
	 * @param {BaseCommand} command - Command to add
	 */
	addCommand(command) {
		this.commands.push(command);
	}

	/**
	 * Removes a command from the composite
	 * @param {BaseCommand} command - Command to remove
	 */
	removeCommand(command) {
		const index = this.commands.indexOf(command);
		if (index > -1) {
			this.commands.splice(index, 1);
		}
	}

	/**
	 * Executes all sub-commands
	 * @returns {boolean} True if all commands executed successfully
	 */
	execute() {
		try {
			for (const command of this.commands) {
				const result = command.execute();
				if (result === false) {
					this.undoExecutedCommands();
					return false;
				}
			}
			this.executed = true;
			return true;
		} catch (error) {
			this.undoExecutedCommands();
			throw error;
		}
	}

	/**
	 * Undoes all sub-commands in reverse order
	 * @returns {boolean} True if all commands undone successfully
	 */
	undo() {
		try {
			for (let i = this.commands.length - 1; i >= 0; i--) {
				const command = this.commands[i];
				if (command.executed) {
					const result = command.undo();
					if (result === false) {
						return false;
					}
				}
			}
			this.executed = false;
			return true;
		} catch (error) {
			console.error('CompositeCommand: Error during undo:', error);
			return false;
		}
	}

	/**
	 * Undoes only the executed commands (for error recovery)
	 */
	undoExecutedCommands() {
		for (let i = this.commands.length - 1; i >= 0; i--) {
			const command = this.commands[i];
			if (command.executed) {
				try {
					command.undo();
				} catch (error) {
					console.error('CompositeCommand: Error undoing executed command:', error);
				}
			}
		}
	}

	/**
	 * Checks if all sub-commands can be undone
	 * @returns {boolean} True if all can be undone
	 */
	canUndo() {
		return this.executed && this.commands.every(cmd => cmd.canUndo());
	}

	/**
	 * Checks if all sub-commands can be redone
	 * @returns {boolean} True if all can be redone
	 */
	canRedo() {
		return !this.executed && this.commands.every(cmd => cmd.canRedo());
	}

	/**
	 * Gets all entities affected by sub-commands
	 * @returns {Array} Array of affected entity IDs
	 */
	getAffectedEntities() {
		const entities = new Set();
		this.commands.forEach(command => {
			command.getAffectedEntities().forEach(id => entities.add(id));
		});
		return Array.from(entities);
	}

	/**
	 * Gets memory usage of all sub-commands
	 * @returns {number} Total memory usage in bytes
	 */
	getMemoryUsage() {
		const baseSize = super.getMemoryUsage();
		const commandsSize = this.commands.reduce((total, cmd) => total + cmd.getMemoryUsage(), 0);
		return baseSize + commandsSize;
	}

	/**
	 * Validates all sub-commands
	 * @returns {Object} Validation result
	 */
	validateParameters() {
		const errors = [];
		
		this.commands.forEach((command, index) => {
			const validation = command.validateParameters();
			if (!validation.valid) {
				errors.push(`Command ${index}: ${validation.errors.join(', ')}`);
			}
		});

		return {
			valid: errors.length === 0,
			errors
		};
	}

	/**
	 * Performs cleanup on all sub-commands
	 */
	cleanup() {
		super.cleanup();
		this.commands.forEach(command => command.cleanup());
		this.commands = [];
	}
}