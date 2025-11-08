import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import './editor.scss';

export default function Edit({ attributes, setAttributes }) {
    const { choiceListId } = attributes;

    // Get all Quick Choice posts
    const choiceLists = useSelect((select) => {
        return select('core').getEntityRecords('postType', 'quick_choice', {
            per_page: -1,
            status: 'publish',
        });
    }, []);

    // Get selected choice list
    const selectedList = useSelect((select) => {
        if (!choiceListId) return null;
        return select('core').getEntityRecord('postType', 'quick_choice', choiceListId);
    }, [choiceListId]);

    const blockProps = useBlockProps({
        className: 'qcm-game-block-editor',
    });

    const options = [
        { label: __('Select a list...', 'quick-choice-movies'), value: 0 },
        ...(choiceLists || []).map((list) => ({
            label: list.title.rendered,
            value: list.id,
        })),
    ];

    return (
        <>
            <InspectorControls>
                <PanelBody title={__('Game Settings', 'quick-choice-movies')}>
                    <SelectControl
                        label={__('Choice List', 'quick-choice-movies')}
                        value={choiceListId}
                        options={options}
                        onChange={(value) => setAttributes({ choiceListId: parseInt(value) })}
                        help={__('Select which list of choices to use for this game.', 'quick-choice-movies')}
                    />
                </PanelBody>
            </InspectorControls>

            <div {...blockProps}>
                <div className="qcm-game-block-preview">
                    {!choiceListId ? (
                        <div className="qcm-game-block-placeholder">
                            <p>{__('Please select a choice list from the block settings.', 'quick-choice-movies')}</p>
                        </div>
                    ) : selectedList ? (
                        <div className="qcm-game-block-info">
                            <h3>{__('Quick Choice Game', 'quick-choice-movies')}</h3>
                            <p>
                                {__('List:', 'quick-choice-movies')} <strong>{selectedList.title.rendered}</strong>
                            </p>
                            <p className="description">
                                {__('The game will be displayed here on the front-end.', 'quick-choice-movies')}
                            </p>
                        </div>
                    ) : (
                        <div className="qcm-game-block-placeholder">
                            <p>{__('Loading...', 'quick-choice-movies')}</p>
                        </div>
                    )}
                </div>
            </div>
        </>
    );
}
