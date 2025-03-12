(function (blocks, element, blockEditor) {
    const { registerBlockType } = blocks;
    const { createElement } = element;
    const { useBlockProps } = blockEditor;

    registerBlockType('wc-custom/filters-block', {
        title: 'daRock WooCommerce Filter',
        icon: 'filter',
        category: 'woocommerce',
        edit: function () {
            return createElement('p', useBlockProps(), 'This block will display daRock WooCommerce Filter.');
        },
        save: function () {
            return null; // dynamically render
        }
    });
})(window.wp.blocks, window.wp.element, window.wp.blockEditor);
