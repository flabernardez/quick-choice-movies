import { useBlockProps } from '@wordpress/block-editor';

export default function save({ attributes }) {
    const { choiceListId } = attributes;

    const blockProps = useBlockProps.save({
        className: 'qcm-game-block',
    });

    return (
        <div {...blockProps} data-choice-list-id={choiceListId}>
            {/* Game will be rendered here by JavaScript */}
        </div>
    );
}
