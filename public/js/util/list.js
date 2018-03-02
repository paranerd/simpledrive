var List = (function() {
	var self;

	// Constructor
	function List(id, displayCallback, multi, updateCallback) {
		// General
		self = this;
		this.id = id;

		// Items
		this.items = [];
		this.masterFiltered = [];
		this.filtered = [];
		this.filterNeedle = '';
		this.filterKeys = null;
		this.defaultFilterKeys = null;
		this.sortKey = null;
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

	// Add methods to prototype
	List.prototype = {
		init: function() {
			$(document).on('keydown', function(e) {
				if ($("#" + self.id).is(":visible") && $("#shield").hasClass("hidden")) {
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

					switch (e.keyCode) {
						case 38: // Up
							if (!e.shiftKey && ($(":focus").length == 0 || $(":focus").hasClass("filter-input"))) {
								self.selectPrev();
							}
							break;

						case 40: // Down
							if (!e.shiftKey && ($(":focus").length == 0 || $(":focus").hasClass("filter-input"))) {
								self.selectNext();
							}
							break;

						case 65: // A
							if (e.ctrlKey && !$(e.target).is('input')) {
								self.selectAll();
							}
							break;
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

		/**
		 * Set list items
		 *
		 * @param array items
		 * @param string orderBy
		 */
		setItems: function(items, orderBy) {
			this.items = items;
			this.masterFiltered = items;
			this.filtered = items;
			this.defaultFilterKeys = (orderBy) ? [orderBy] : [];
			this.currentSelected = -1;

			this.filterRemove();

			if (orderBy || this.sortKey) {
				this.order(orderBy, this.sortOrder);
			}
		},

		/**
		 * Set comparator method
		 *
		 * @param function comparator
		 */
		setComparator: function(comparator) {
			this.compare = comparator;
		},

		/**
		 * Add item to list
		 *
		 * @param Item item
		 */
		add: function(item) {
			this.items.push(item);
			this.filter(this.filterNeedle, this.filterKeys);
			this.display();
		},

		/**
		 * Update list item
		 *
		 * @param int id
		 * @param Item item
		 */
		update: function(id, item) {
			if (this.items.length > id) {
				this.items[id] = item;
				this.display();
			}
		},

		/**
		 * Remove item from list
		 *
		 * @param int id
		 */
		remove: function(id) {
			if (this.filtered.length <= id) {
				return;
			}

			// Map filtered to items
			for (var d in this.items) {
				if (this.filtered[id] == this.items[d]) {
					this.items.splice(d, 1);
					this.filter(this.filterNeedle, this.filterKeys);
					this.display();
					break;
				}
			}
		},

		/**
		 * Select item
		 *
		 * @param int id
		 */
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

		/**
		 * Unselect item
		 *
		 * @param int id
		 */
		unselect: function(id) {
			delete this.selected[id];

			this.updateSelections();
			if (this.updateCallback) {
				this.updateCallback();
			}
		},

		/**
		 * Select all items
		 */
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

		/**
		 * Unselect all items
		 */
		unselectAll: function() {
			this.selected = {};

			this.updateSelections();
			if (this.updateCallback) {
				this.updateCallback();
			}
		},

		/**
		 * Select next item
		 *
		 * @param int id
		 */
		selectNext: function() {
			this.unselectAll();
			this.currentSelected = (this.currentSelected < this.filtered.length - 1) ? this.currentSelected + 1 : this.filtered.length -1;
			this.select(this.currentSelected);
		},

		/**
		 * Select previous item
		 *
		 * @param int id
		 */
		selectPrev: function() {
			this.unselectAll();
			this.currentSelected = (this.currentSelected > 0) ? this.currentSelected - 1 : 0;
			this.select(this.currentSelected);
		},

		/**
		 * Get all selected item IDs
		 */
		getAllSelectedIDs: function() {
			var ids = [];
			for (var i in this.selected) {
				ids.push(this.selected[i].id);
			}

			return ids;
		},

		/**
		 * Get all selected items
		 */
		getAllSelected: function() {
			return this.selected;
		},

		/**
		 * Get first selected item
		 */
		getFirstSelected: function() {
			for (var first in this.selected) break;
			return {id: first, item: this.selected[first]};
		},

		/**
		 * Get selected item at position
		 *
		 * @param int id
		 */
		getSelectedAt: function(id) {
			return this.selected[id];
		},

		/**
		 * Get count of selected items
		 */
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

		/**
		 * Toggle between select all and select none
		 */
		toggleAllSelection: function() {
			if (Object.keys(this.selected).length > 0) {
				this.unselectAll();
			}
			else {
				this.selectAll();
			}
		},

		/**
		 * Get item at position
		 *
		 * @param int id
		 */
		get: function(id) {
			return (id >= 0 && id < this.filtered.length) ? this.filtered[id] : null;
		},

		/**
		 * Get item by key and value
		 *
		 * @param string key
		 * @param string value
		 */
		getByKey: function(key, value) {
			for (var i in this.filtered) {
				var item = this.filtered[i];
				if (item[key] == value) {
					return item;
				}
			}
		},

		/**
		 * Get all filtered items
		 */
		getAllFiltered: function() {
			return this.filtered;
		},

		/**
		 * Get all items
		 */
		getAll: function() {
			return this.items;
		},

		/**
		 * Add a loading-placeholder or indicator of empty folder
		 *
		 * @param string msg
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
		 * Handle selection hightlighting
		 *
		 * @param int id
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

		/**
		 * A filter before the filter
		 * e.g. for filtering in gallery mode
		 *
		 * @param string needle
		 * @param array keys
		 */
		masterFilter: function(needle, keys) {
			this.masterFiltered = this.filter(needle, keys);
		},

		/**
		 * Set empty view or nvoke displayCallback
		 */
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

		/**
		 * Filter items
		 *
		 * @param string needle
		 * @param array keys
		 */
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
				if (document.activeElement) {
					document.activeElement.blur();
				}
			}

			return this.filtered;
		},

		/**
		 * Remove master filter
		 */
		masterFilterRemove: function() {
			this.masterFiltered = this.items;
			this.filterRemove();
		},

		/**
		 * Remove filter
		 */
		filterRemove: function() {
			$("#" + this.id + "-filter").addClass("hidden");
			$(".filter-input").val('');
			return this.filter('');
		},

		/**
		 * Order items
		 *
		 * @param string key
		 * @param int order
		 */
		order: function(key, order) {
			this.sortKey = key;
			this.sortOrder = (order) ? order : this.sortOrder *= -1;
			this.items = this.items.sort(this.compare(this.sortKey, this.sortOrder));

			var text = (this.sortOrder === 1) ? "&nbsp &#x25B4" : "&nbsp &#x25BE";
			$(".order-direction").text('');
			$("#" + key + "-ord").html(text);
			this.filter(this.filterNeedle, this.filterKeys);
		},

		/**
		 * Compare items
		 *
		 * @param string key
		 * @param int order
		 */
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