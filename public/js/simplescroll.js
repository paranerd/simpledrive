/**
 * @author		Kevin Schulz <paranerd.development@gmail.com>
 * @copyright	(c) 2017, Kevin Schulz. All Rights Reserved
 * @license		Affero General Public License <http://www.gnu.org/licenses/agpl>
 * @link		http://simpledrive.org
 */

var simpleScroll = {
	hasBeenInitialized: false,
	scrollpercent: 0,
	diff: 0,
	autoScrollBottom: false,
	self: null,
	containers: [],
	scrollbarWidth: 0,
	scrolling: null,

	offset: function(elem) {
		var offset = {top: 0, left: 0};
		var node = elem;

		while (node != null) {
			offset.top += (!isNaN(node.offsetTop)) ? node.offsetTop : 0;
			node = node.parentNode;
		}

		return offset;
	},

	init: function(id) {
		if (!this.hasBeenInitialized) {
			self = this;
			self.scrollbarWidth = self.getScrollBarWidth();

			document.onmouseup = function() {
				self.scrolling = null;
				//simpleScrollContainer.style["MozUserSelect"] = "all";
			}

			document.onmousemove = function(e) {
				var id = self.scrolling;
				var container = self.getSSC(self.scrolling);
				if (container != null) {
					// Calculate the scroll amount by dividing the top pixel of the scrollbar by all the pixels it could move (container height minus scrollbar height)
					container.scrollpercent = (e.pageY - self.offset(container.elem).top + container.diff) / ($("#" + container.id).height() - $("#scrollbar" + id).height());
					container.elem.scrollTop = (container.elem.scrollHeight - $("#" + container.id).height()) * container.scrollpercent;
					$("#scrollbar" + id).css('top', ($("#" + container.id).height() - $("#scrollbar" + id).height()) * (container.elem.scrollTop / (container.elem.scrollHeight - $("#" + container.id).height())));
				}
			}
			this.hasBeenInitialized = true;
		}

		var container = document.getElementById(id);

		if (container == null) {
			return;
		}

		var content = container.innerHTML;
		container.innerHTML = "";

		var innerNode = document.createElement("div");
		innerNode.id = "simpleScrollContainer" + self.containers.length;
		innerNode.style.width = ($("#" + id).width() + self.scrollbarWidth) + "px";
		//innerNode.style.width = '5px';
		innerNode.style.height = $("#" + id).height() + "px";
		innerNode.style.overflow = "auto";
		innerNode.innerHTML = content;

		container.appendChild(innerNode);

		/* To keep event listeners, use the following for above:
		 * var content = $("#" + id).clone(true, true);
		 * scratch "innerNode.innerHTML = content;
		 * while ($(content).children().length > 0) {
		 *		$(innerNode).append($($(content).children()[0]).clone(true, true));
		 *		$($(content).children()[0]).remove();
		 * }
		 */

		var scrolly = document.createElement("div");
		scrolly.id = "scrollbar" + self.containers.length;
		scrolly.className = "scrollbar";
		scrolly.style.height = "0%";

		container.appendChild(scrolly);

		// Register event callbacks
		scrolly.onmousedown = function(e) {
			var id = this.id.substring(9);
			var container = self.getSSC(id);
			if (container != null) {
				container.diff = self.offset(this).top - e.pageY;
				//simpleScrollContainer.style["MozUserSelect"] = "none";
				self.scrolling = id;
			}
		}

		innerNode.onscroll = function() {
			if (self.scrolling == null) {
				self.update();
			}
		}

		self.containers.push({id: id, elem: innerNode, diff: 0, scrollpercent: 0});
		self.update();
	},

	append: function(id, html) {
		var container = self.getContainer(id);

		if (container == null) {
			return;
		}

		if ((typeof html !== "object") || (html.nodeType !== 1) || (typeof html.tagName !== "string")) {
			var wrapper = document.createElement('div');
			wrapper.innerHTML = html;
			while (wrapper.hasChildNodes()) {
				container.elem.appendChild(wrapper.removeChild(wrapper.firstChild));
			}
		}
		else {
			container.elem.appendChild(html);
		}
	},

	update: function() {
		if (self.containers.length == 0) {
			return;
		}

		for (var i = 0; i < self.containers.length; i++) {
			var container = self.containers[i];
			var scrollbar = document.getElementById("scrollbar" + i);

			container.elem.style.height = $("#" + container.id).height() + "px";

			// Put scrollbar where it belongs
			$("#scrollbar" + i).height(($("#" + container.id).height() / container.elem.scrollHeight) * $("#" + container.id).height() + "px");
			if (self.autoScrollBottom) {
				container.elem.scrollTop = container.elem.scrollHeight - $("#" + container.id).height();
			}

			$("#scrollbar" + i).css('top', ($("#" + container.id).height() - $("#scrollbar" + i).height()) * (container.elem.scrollTop / (container.elem.scrollHeight - $("#" + container.id).height())) + "px");

			if ($("#scrollbar" + i).height() == container.elem.scrollHeight) {
				scrollbar.style.display = "none";
				container.elem.style.width = ($("#" + container.id).width()) + "px";
			}
			else {
				scrollbar.style.display = "block";
				container.elem.style.width = ($("#" + container.id).width() + self.scrollbarWidth) + "px";
			}
		}
	},

	empty: function(id) {
		var container = self.getContainer(id);

		if (container == null) {
			return;
		}

		container.elem.innerHTML = "";
	},

	getContainer: function(id) {
		for (var i = 0; i < self.containers.length; i++) {
			if (self.containers[i].id == id) {
				return self.containers[i];
			}
		}

		return null;
	},

	getSSC: function(id) {
		for (var i = 0; i < self.containers.length; i++) {
			if (self.containers[i].elem.id == "simpleScrollContainer" + id) {
				return self.containers[i];
			}
		}

		return null;
	},

	getScrollBarWidth: function() {
	  var inner = document.createElement('p');
	  inner.style.width = "100%";
	  inner.style.height = "200px";

	  var outer = document.createElement('div');
	  outer.style.position = "absolute";
	  outer.style.top = "0px";
	  outer.style.left = "0px";
	  outer.style.visibility = "hidden";
	  outer.style.width = "50px";
	  outer.style.height = "100px";
	  outer.style.overflow = "hidden";
	  outer.appendChild(inner);

	  document.body.appendChild(outer);
	  var w1 = inner.offsetWidth;
	  outer.style.overflow = 'scroll';
	  var w2 = inner.offsetWidth;
	  if (w1 == w2) w2 = outer.clientWidth;

	  document.body.removeChild(outer);

	  return (w1 - w2);
	}
}
