M.block_meinesuche_collapse = {
    init: function(Y) {
        // Fix the bug in YUI treeview that breaks embedded images.
        Y.YUI2.widget.TextNode.prototype.getContentHtml = function() {
            var sb = [];
            sb[sb.length] = this.href ? '<a' : '<span';
            sb[sb.length] = ' id="' + Y.YUI2.lang.escapeHTML(this.labelElId) + '"';
            sb[sb.length] = ' class="' + Y.YUI2.lang.escapeHTML(this.labelStyle) + '"';
            if (this.href) {
                sb[sb.length] = ' href="' + Y.YUI2.lang.escapeHTML(this.href) + '"';
                sb[sb.length] = ' target="' + Y.YUI2.lang.escapeHTML(this.target) + '"';
            }
            if (this.title) {
                sb[sb.length] = ' title="' + Y.YUI2.lang.escapeHTML(this.title) + '"';
            }
            sb[sb.length] = ' >';
            sb[sb.length] = this.label;
            sb[sb.length] = this.href?'</a>':'</span>';
            return sb.join("");
        };
        // Convert the course list into a tree view.
        var toc = new Y.YUI2.widget.TreeView('meinesuche_coursetree');
        toc.render();
    }
};
