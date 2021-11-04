const { addFilter } = wp.hooks;
const { createInterpolateElement } = wp.element;
const { createHigherOrderComponent } = wp.compose;
const { addQueryArgs } = wp.url;
const { InspectorControls } = ( tainacan_blocks.wp_version < '5.2' ? wp.editor : wp.blockEditor );
const { __ } = wp.i18n;

/**
 * This function creates a link to the Tainacan Admin panel
 * from the Query Loop panel if a Tainacan Collection is being queried.
 */
const CreateNewTainacanItemLink = ( {
	attributes: { query: { postType } = {} } = {},
} ) => {
	if ( ! postType || ! postType.includes('tnc_col_')) return null;

	const newItemUrl = addQueryArgs( 'admin.php', {
		page: 'tainacan_admin#/collections/' + postType.split('_')[2] + '/items/new',
	} );
	return (
		<div className="wp-block-query__create-new-link">
			{ createInterpolateElement(
				__( '<a>Create a new item</a> for this collection.', 'tainacan' ),
				// eslint-disable-next-line jsx-a11y/anchor-has-content
				{ a: <a href={ newItemUrl } /> }
			) }
		</div>
	);
};

/**
 * Override the default edit UI to include layout controls
 *
 * @param {Function} BlockEdit Original component
 * @return {Function}           Wrapped component
 */
const tainacanUpdateQueryTopInspectorControls = createHigherOrderComponent(
	( BlockEdit ) => ( props ) => {
		const { name, isSelected } = props;
		if ( name !== 'core/query' || ! isSelected ) {
			return <BlockEdit key="edit" { ...props } />;
		}

		return (
			<>
				<InspectorControls>
					<CreateNewTainacanItemLink { ...props } />
				</InspectorControls>
				<BlockEdit key="edit" { ...props } />
			</>
		);
	},
	'withInspectorControls'
);
addFilter( 'editor.BlockEdit', 'core/query', tainacanUpdateQueryTopInspectorControls );