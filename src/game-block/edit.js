import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import './editor.scss';

export default function Edit({ attributes, setAttributes }) {
    const { gameType, choiceListId } = attributes;

    // Get posts based on game type
    const postType = gameType === 'tier_list' ? 'tier_list' : 'quick_choice';

    const gameLists = useSelect((select) => {
        return select('core').getEntityRecords('postType', postType, {
            per_page: -1,
            status: 'publish',
        });
    }, [postType]);

    const selectedList = useSelect((select) => {
        if (!choiceListId) return null;
        return select('core').getEntityRecord('postType', postType, choiceListId);
    }, [choiceListId, postType]);

    const blockProps = useBlockProps({
        className: 'qcm-game-block-editor',
    });

    const gameOptions = [
        { label: __('Select a list...', 'quick-choice-movies'), value: 0 },
        ...(gameLists || []).map((list) => ({
            label: list.title.rendered,
            value: list.id,
        })),
    ];

    // Reset selection when game type changes
    const handleGameTypeChange = (newType) => {
        setAttributes({ gameType: newType, choiceListId: 0 });
    };

    return (
        <>
            <InspectorControls>
                <PanelBody title={__('Game Settings', 'quick-choice-movies')}>
                    <SelectControl
                        label={__('Game Type', 'quick-choice-movies')}
                        value={gameType}
                        options={[
                            { label: __('Quick Choice', 'quick-choice-movies'), value: 'quick_choice' },
                            { label: __('Tier List', 'quick-choice-movies'), value: 'tier_list' },
                        ]}
                        onChange={handleGameTypeChange}
                        help={__('Select the type of game to display.', 'quick-choice-movies')}
                    />
                    <SelectControl
                        label={gameType === 'tier_list' ? __('Tier List', 'quick-choice-movies') : __('Choice List', 'quick-choice-movies')}
                        value={choiceListId}
                        options={gameOptions}
                        onChange={(value) => setAttributes({ choiceListId: parseInt(value) })}
                        help={__('Select which list to use for this game.', 'quick-choice-movies')}
                    />
                </PanelBody>
            </InspectorControls>

            <div {...blockProps}>
                <div className="qcm-game-block-preview">
                    {!choiceListId ? (
                        <div className="qcm-game-block-placeholder">
                            <p>{__('Please select a game type and list from the block settings.', 'quick-choice-movies')}</p>
                        </div>
                    ) : selectedList ? (
                        <div className="qcm-game-block-info">
                            <h3>
                                {gameType === 'tier_list'
                                    ? __('Tier List', 'quick-choice-movies')
                                    : __('Quick Choice', 'quick-choice-movies')
                                }
                            </h3>
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
