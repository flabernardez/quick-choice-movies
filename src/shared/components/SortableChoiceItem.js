import { __ } from '@wordpress/i18n';
import { Button, TextControl } from '@wordpress/components';
import { MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';
import { trash, dragHandle } from '@wordpress/icons';
import { useSortable } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';

export default function SortableChoiceItem({ item, index, onUpdate, onRemove }) {
    const {
        attributes,
        listeners,
        setNodeRef,
        transform,
        transition,
    } = useSortable({ id: item.id });

    const style = {
        transform: CSS.Transform.toString(transform),
        transition,
    };

    return (
        <div ref={setNodeRef} style={style} className="qcm-choice-item">
            <div className="qcm-choice-item__drag" {...attributes} {...listeners}>
                <Button icon={dragHandle} />
            </div>
            <div className="qcm-choice-item__image">
                {item.image ? (
                    <img src={item.image} alt={item.title} />
                ) : (
                    <div className="qcm-choice-item__image-placeholder">
                        {__('No image', 'quick-choice-movies')}
                    </div>
                )}
            </div>
            <div className="qcm-choice-item__content">
                <TextControl
                    label={__('Title', 'quick-choice-movies')}
                    value={item.title}
                    onChange={(value) => onUpdate(index, { ...item, title: value })}
                />
                <MediaUploadCheck>
                    <MediaUpload
                        onSelect={(media) => onUpdate(index, { ...item, image: media.url })}
                        allowedTypes={['image']}
                        value={item.image}
                        render={({ open }) => (
                            <Button variant="secondary" onClick={open}>
                                {item.image ? __('Change Image', 'quick-choice-movies') : __('Upload Image', 'quick-choice-movies')}
                            </Button>
                        )}
                    />
                </MediaUploadCheck>
            </div>
            <div className="qcm-choice-item__actions">
                <Button
                    icon={trash}
                    variant="tertiary"
                    isDestructive
                    onClick={() => onRemove(index)}
                />
            </div>
        </div>
    );
}
