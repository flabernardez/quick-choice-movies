import { useBlockProps } from '@wordpress/block-editor';

export default function save() {
    const blockProps = useBlockProps.save({
        className: 'qcm-list-block',
    });

    return (
        <div {...blockProps}>
            {/* List will be rendered by PHP */}
        </div>
    );
}
