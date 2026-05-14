/**
 * AimaraJS - A simple and lightweight tree component in pure JavaScript.
 * (Lightweight compatible implementation for BrainNotes)
 */
function Tree(containerId) {
    this.container = document.getElementById(containerId);
    this.nodes = [];
    this.selectedNode = null;
    this.onNodeSelected = null;
    this.onRenderActions = null;
    this.afterDraw = null;

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
        ul.setAttribute('data-id', '0');
        for (var i = 0; i < this.nodes.length; i++) {
            this.renderNode(this.nodes[i], ul);
        }
        this.container.appendChild(ul);
        
        if (this.afterDraw) {
            this.afterDraw();
        }
    };

    this.renderNode = function(node, parentElement) {
        var li = document.createElement('li');
        li.className = 'aimara-node';
        if (node.children.length > 0) {
            li.classList.add(node.expanded ? 'expanded' : 'collapsed');
        }

        var wrapper = document.createElement('div');
        wrapper.className = 'node-wrapper';
        wrapper.setAttribute('data-id', node.id);
        wrapper.setAttribute('data-type', 'section');
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

        /* Action Buttons via Callback */
        if (this.onRenderActions) {
            var actions = this.onRenderActions(node);
            if (actions) {
                wrapper.appendChild(actions);
            }
        }

        wrapper.onclick = () => {
            this.selectedNode = node;
            this.drawTree();
            if (this.onNodeSelected) this.onNodeSelected(node);
        };

        li.appendChild(wrapper);
        node.element = wrapper;
        node.li = li;

        // Always render the child UL to allow dropping into it, but hide if not expanded
        var childUl = document.createElement('ul');
        childUl.className = 'aimara-tree-sub'; // Distinct class for nested lists
        childUl.setAttribute('data-id', node.id);
        if (node.children.length > 0) {
            if (node.expanded) {
                for (var i = 0; i < node.children.length; i++) {
                    this.renderNode(node.children[i], childUl);
                }
            } else {
                childUl.style.display = 'none';
            }
        } else {
            // Even if no children, keep the UL as a potential drop target
            childUl.style.display = node.expanded ? 'block' : 'none';
            childUl.classList.add('empty-dropzone');
        }
        li.appendChild(childUl);

        parentElement.appendChild(li);
    };
}
