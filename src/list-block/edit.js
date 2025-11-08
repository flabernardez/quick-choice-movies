import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';
import { useSelect } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import './editor.scss';

export default function Edit() {
    const blockProps = useBlockProps({
        className: 'qcm-list-block-editor',
    });

    const { postId, items } = useSelect((select) => {
        const currentPostId = select(editorStore).getCurrentPostId();
        const meta = select(editorStore).getEditedPostAttribute('meta');
        const itemsJson = meta?.qcm_choice_items || '';

        let parsedItems = [];
        if (itemsJson) {
            try {
                parsedItems = JSON.parse(itemsJson);
            } catch (e) {
                console.error('Error parsing items:', e);
            }
        }

        return {
            postId: currentPostId,
            items: parsedItems,
        };
    }, []);

    return (
        <div {...blockProps}>
            <div className="qcm-list-preview">
                <h3>{__('Quick Choice Items List', 'quick-choice-movies')}</h3>
                {items.length === 0 ? (
                    <p className="qcm-list-empty">
                        {__('No items yet. Add items in the "Quick Choice Items" section below.', 'quick-choice-movies')}
                    </p>
                ) : (
                    <div className="qcm-list-preview-grid">
                        {items.map((item, index) => (
                            <div key={item.id || index} className="qcm-list-preview-item">
                                {item.image && (
                                    <img src={item.image} alt={item.title} />
                                )}
                                <span>{item.title}</span>
                            </div>
                        ))}
                    </div>
                )}
                <p className="qcm-list-count">
                    <strong>{items.length}</strong> {__('items will be displayed on the front-end', 'quick-choice-movies')}
                </p>
            </div>
        </div>
    );
}
