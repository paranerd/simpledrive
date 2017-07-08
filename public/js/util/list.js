function List (callback, multi) {
	this.data = [];
	this.filtered = [];
	this.filterNeedle = '';
	this.filterKey = null;
	this.sortOrder = 1; // 1: asc, -1: desc
	this.selected = {};
	this.currentSelected = -1;
	this.callback = callback;
	this.multiselect = (multi) ? true : false;

	this.setData = function(data) {
		this.data = data;
		this.unselectAll();
	};

	this.select = function(id) {
		if (this.data.length > id) {
			if (!this.multiselect) {
				this.unselectAll();
			}

			this.selected[id] = this.data[id];
			this.currentSelected = parseInt(id);

			this.updateSelections();
			if (this.callback) {
				this.callback(id);
			}
		}
	};

	this.unselect = function(id) {
		delete this.selected[id];

		this.updateSelections();
		if (this.callback) {
			this.callback();
		}
	};

	this.selectAll = function() {
		if (this.multiselect) {
			for (var i = 0; i < Object.keys(this.data).length; i++) {
				this.selected[i] = this.data[i];
			}
		}

		this.updateSelections();
		if (this.callback) {
			this.callback();
		}
	};

	this.unselectAll = function() {
		this.selected = {};

		this.updateSelections();
		if (this.callback) {
			this.callback();
		}
	};

	this.selectNext = function() {
		this.unselectAll();
		this.currentSelected = (this.currentSelected < this.data.length - 1) ? this.currentSelected + 1 : this.data.length -1;
		this.select(this.currentSelected);
	};

	this.selectPrev = function() {
		this.unselectAll();
		this.currentSelected = (this.currentSelected > 0) ? this.currentSelected - 1 : 0;
		this.select(this.currentSelected);
	};

	this.getAllSelectedIDs = function() {
		var ids = [];
		for (var i in this.selected) {
			ids.push(this.selected[i].id);
		}

		return ids;
	};

	this.getAllSelected = function() {
		return this.selected;
	};

	this.getFirstSelected = function() {
		for (var first in this.selected) break;
		return {id: first, item: this.selected[first]};
	};

	this.getSelectedAt = function(id) {
		return this.selected[id];
	};

	this.getSelectedCount = function() {
		return Object.keys(this.selected).length;
	};

	this.toggleSelection = function(id) {
		// Un-select
		if (typeof this.getSelectedAt(id) !== "undefined") {
			this.unselect(id);
		}
		// Select
		else {
			this.select(id);
		}
	};

	this.toggleAllSelection = function() {
		if (Object.keys(this.selected).length > 0) {
			this.unselectAll();
		}
		else {
			this.selectAll();
		}
	};

	this.get = function(id) {
		return (id >= 0 && id < this.data.length) ? this.data[id] : null;
	};

	this.getAll = function() {
		return this.data;
	};

	this.getAllCount = function() {
		return this.data.length;
	};

	/**
	 * Adds a loading-placeholder or indicator of empty folder
	 */
	this.setEmptyView = function(id, msg) {
		var empty = document.createElement("div");
		empty.style.lineHeight = $("#" + id).height() + "px";
		empty.className = "empty";
		empty.innerHTML = (msg) ? msg : "Nothing to see here...";
		simpleScroll.append(id, empty);
		simpleScroll.update();
	};

	/**
	 * Handles selection hightlighting
	 */
	this.updateSelections = function(id) {
		// Reset all selected status
		$(".item").removeClass("selected");

		for (var i in this.selected) {
			$("#item" + i).addClass("selected");
		}

		var count = this.getSelectedCount();

		// Update selection-checkbox
		if (count > 0 && count == this.getAll().length) {
			$("#checker").addClass("checkbox-checked");
		}
		else {
			$("#checker").removeClass("checkbox-checked");
		}
	};
}