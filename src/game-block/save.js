import { useBlockProps } from '@wordpress/block-editor';

export default function save({ attributes }) {
    const { gameType, choiceListId } = attributes;

    const blockProps = useBlockProps.save({
        className: gameType === 'tier_list' ? 'qcm-tierlist-block' : 'qcm-game-block',
    });

    const dataAttr = gameType === 'tier_list' ? 'data-tier-list-id' : 'data-choice-list-id';

    return (
        <div {...blockProps} {...{ [dataAttr]: choiceListId }}>
            {/* Game will be rendered here by JavaScript */}
        </div>
    );
}
