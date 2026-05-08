/**
 * AimaraJS - A simple and lightweight tree component in pure JavaScript.
 * (Lightweight compatible implementation for BrainNotes)
 */
function Tree(containerId) {
    this.container = document.getElementById(containerId);
    this.nodes = [];
    this.selectedNode = null;
    this.onNodeSelected = null;

    this.createNode = function(text, id, icon, parent, expanded) {
        var node = {
            text: text,
            id: id,
            icon: icon,
            parent: parent,
            expanded: expanded || false,
            children: [],
            element: null,
            li: null
        };

        if (parent) {
            parent.children.push(node);
        } else {
            this.nodes.push(node);
        }

        return node;
    };

    this.drawTree = function() {
        this.container.innerHTML = '';
        var ul = document.createElement('ul');
        ul.className = 'aimara-tree';
        for (var i = 0; i < this.nodes.length; i++) {
            this.renderNode(this.nodes[i], ul);
        }
        this.container.appendChild(ul);
    };

    this.renderNode = function(node, parentElement) {
        var li = document.createElement('li');
        li.className = 'aimara-node';
        if (node.children.length > 0) {
            li.classList.add(node.expanded ? 'expanded' : 'collapsed');
        }

        var wrapper = document.createElement('div');
        wrapper.className = 'node-wrapper';
        if (this.selectedNode === node) wrapper.classList.add('selected');

        // Toggle button
        if (node.children.length > 0) {
            var toggle = document.createElement('span');
            toggle.className = 'node-toggle';
            toggle.onclick = (e) => {
                e.stopPropagation();
                node.expanded = !node.expanded;
                this.drawTree();
            };
            wrapper.appendChild(toggle);
        } else {
            var spacer = document.createElement('span');
            spacer.className = 'node-spacer';
            wrapper.appendChild(spacer);
        }

        // Icon
        if (node.icon) {
            var icon = document.createElement('i');
            icon.className = node.icon + ' node-icon';
            wrapper.appendChild(icon);
        }

        // Text
        var text = document.createElement('span');
        text.className = 'node-text';
        text.innerText = node.text;
        wrapper.appendChild(text);

        wrapper.onclick = () => {
            this.selectedNode = node;
            this.drawTree();
            if (this.onNodeSelected) this.onNodeSelected(node);
        };

        li.appendChild(wrapper);
        node.element = wrapper;
        node.li = li;

        if (node.children.length > 0 && node.expanded) {
            var childUl = document.createElement('ul');
            for (var i = 0; i < node.children.length; i++) {
                this.renderNode(node.children[i], childUl);
            }
            li.appendChild(childUl);
        }

        parentElement.appendChild(li);
    };
}
