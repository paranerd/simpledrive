var List = (function() {
	var self;

	// Constructor
	function List(id, displayCallback, multi, updateCallback) {
		// General
		self = this;
		this.id = id;

		// Data
		this.data = [];
		this.masterFiltered = [];
		this.filtered = [];
		this.filterNeedle = '';
		this.filterKeys = null;
		this.defaultFilterKeys = null;
		this.sortOrder = 1; // 1: asc, -1: desc

		// Selection
		this.multiselect = (multi) ? true : false;
		this.selected = {};
		this.currentSelected = -1;

		// Callbacks
		this.updateCallback = updateCallback;
		this.displayCallback = displayCallback;

		// Initialize
		this.init();
	}

	// Methods
	List.prototype = {
		init: function() {
			$(document).on('keydown', function(e) {
				if ($("#" + self.id).is(":visible")) {
					// Filter
					if (!e.shiftKey && !e.ctrlKey &&
						$(":focus").length == 0 &&
						$("#" + self.id + "-filter").hasClass('hidden') &&
						String.fromCharCode(e.keyCode).toLowerCase().match(/^[\wöäüß]+$/i))
					{
						$("#" + self.id + "-filter").removeClass('hidden');
						$("#" + self.id + "-filter .filter-input").focus();

						setTimeout(function() {
							// Place cursor behind text
							$("#" + self.id + "-filter .filter-input").val(String.fromCharCode(e.keyCode).toLowerCase());
						}, 10);
					}
				}
			});

			$(document).on('keyup', function(e) {
				switch(e.keyCode) {
					case 27: // Esc
						self.filterRemove();
						break;
				}
			});

			$(".content-header > span").on('click', function(e) {
				if ($(this).data('sortby')) {
					self.order($(this).data('sortby'));
				}
			});

			$("#" + self.id + "-filter .close").on('click', function(e) {
				self.filterRemove();
			});

			$("#" + this.id + "-filter .filter-input").on('input', function(e) {
				self.filter($(this).val());
			});

			simpleScroll.init(self.id);
		},

		setData: function(data, orderBy) {
			this.data = data;
			this.masterFiltered = data;
			this.filtered = data;
			this.defaultFilterKeys = (orderBy) ? [orderBy] : [];
			this.currentSelected = -1;

			this.filterRemove();

			if (orderBy) {
				this.order(orderBy, 1);
			}

			this.display();
		},

		setComparator: function(comparator) {
			this.compare = comparator;
		},

		add: function(data) {
			this.data.push(data);
			this.display();
		},

		update: function(id, data) {
			if (this.data.length > id) {
				this.data[id] = data;
				this.display();
			}
		},

		select: function(id) {
			if (this.filtered.length > id) {
				if (!this.multiselect) {
					this.unselectAll();
				}

				this.selected[id] = this.filtered[id];
				this.currentSelected = parseInt(id);

				this.updateSelections();
				if (this.updateCallback) {
					this.updateCallback(id);
				}
			}
		},

		unselect: function(id) {
			delete this.selected[id];

			this.updateSelections();
			if (this.updateCallback) {
				this.updateCallback();
			}
		},

		selectAll: function() {
			if (this.multiselect) {
				for (var i = 0; i < Object.keys(this.filtered).length; i++) {
					this.selected[i] = this.filtered[i];
				}
			}

			this.updateSelections();
			if (this.updateCallback) {
				this.updateCallback();
			}
		},

		unselectAll: function() {
			this.selected = {};

			this.updateSelections();
			if (this.updateCallback) {
				this.updateCallback();
			}
		},

		selectNext: function() {
			this.unselectAll();
			this.currentSelected = (this.currentSelected < this.filtered.length - 1) ? this.currentSelected + 1 : this.filtered.length -1;
			this.select(this.currentSelected);
		},

		selectPrev: function() {
			this.unselectAll();
			this.currentSelected = (this.currentSelected > 0) ? this.currentSelected - 1 : 0;
			this.select(this.currentSelected);
		},

		getAllSelectedIDs: function() {
			var ids = [];
			for (var i in this.selected) {
				ids.push(this.selected[i].id);
			}

			return ids;
		},

		getAllSelected:function() {
			return this.selected;
		},

		getFirstSelected: function() {
			for (var first in this.selected) break;
			return {id: first, item: this.selected[first]};
		},

		getSelectedAt: function(id) {
			return this.selected[id];
		},

		getSelectedCount: function() {
			return Object.keys(this.selected).length;
		},

		toggleSelection: function(id) {
			// Un-select
			if (typeof this.getSelectedAt(id) !== "undefined") {
				this.unselect(id);
			}
			// Select
			else {
				this.select(id);
			}
		},

		toggleAllSelection: function() {
			if (Object.keys(this.selected).length > 0) {
				this.unselectAll();
			}
			else {
				this.selectAll();
			}
		},

		get: function(id) {
			return (id >= 0 && id < this.filtered.length) ? this.filtered[id] : null;
		},

		getAllFiltered: function() {
			return this.filtered;
		},

		getAll: function() {
			return this.data;
		},

		/**
		 * Adds a loading-placeholder or indicator of empty folder
		 */
		setEmptyView: function(msg) {
			simpleScroll.empty(self.id);
			var empty = document.createElement("div");
			empty.style.lineHeight = $("#" + self.id).height() + "px";
			empty.className = "empty";
			empty.innerHTML = (msg) ? msg : "Nothing to see here...";
			simpleScroll.append(self.id, empty);
			simpleScroll.update();
		},

		/**
		 * Handles selection hightlighting
		 */
		updateSelections: function(id) {
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
		},

		/*
		 * A filter before the filter
		 * e.g. for filtering in gallery mode
		 */
		masterFilter: function(needle, keys) {
			this.masterFiltered = this.filter(needle, keys);
		},

		display: function() {
			if (!this.filtered || this.filtered.length == 0) {
				this.setEmptyView();
			}
			else if (this.displayCallback) {
				simpleScroll.empty(self.id);
				this.displayCallback(this.filtered);
				simpleScroll.update(self.id);
			}
		},

		filter: function(needle, keys) {
			this.filterNeedle = needle;
			this.filterKeys = (keys) ? keys : this.defaultFilterKeys;
			this.filtered = Util.filter(this.masterFiltered, needle, this.filterKeys);

			this.display();

			if (needle) {
				this.select(0);
			}
			else {
				this.unselectAll();
				$("#" + this.id + "-filter").addClass("hidden");
				document.activeElement.blur();
			}

			return this.filtered;
		},

		masterFilterRemove: function() {
			this.masterFiltered = this.data;
			this.filterRemove();
		},

		filterRemove: function() {
			$("#" + this.id + "-filter").addClass("hidden");
			$(".filter-input").val('');
			return this.filter('');
		},

		order: function(key, order) {
			this.sortOrder = (order) ? order : this.sortOrder *= -1;
			this.data = this.data.sort(this.compare(key, this.sortOrder));

			var text = (this.sortOrder === 1) ? "&nbsp &#x25B4" : "&nbsp &#x25BE";
			$(".order-direction").text('');
			$("#" + key + "-ord").html(text);
			this.filter(this.filterNeedle, this.filterKeys);
		},

		compare: function(key, order) {
			return function(a, b) {
				if (a[key].toString().toLowerCase() > b[key].toString().toLowerCase()) return order * 1;
				if (a[key].toString().toLowerCase() < b[key].toString().toLowerCase()) return order * -1;
				return 0;
			}
		},
	}

	return List;
})();