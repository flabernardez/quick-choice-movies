import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
    Button,
    Panel,
    PanelBody,
    PanelRow,
    TextControl,
    SelectControl,
    Spinner,
    Card,
    CardHeader,
    CardBody,
    CardFooter,
    Modal,
    SearchControl,
} from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import { MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';
import { trash, dragHandle } from '@wordpress/icons';
import { DndContext, closestCenter, KeyboardSensor, PointerSensor, useSensor, useSensors } from '@dnd-kit/core';
import { arrayMove, SortableContext, sortableKeyboardCoordinates, verticalListSortingStrategy, useSortable } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';

// Sortable Item Component
function SortableChoiceItem({ item, index, onUpdate, onRemove }) {
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

// API Search Modal Component
function APISearchModal({ isOpen, onClose, onSelect }) {
    const [apiSource, setApiSource] = useState('tmdb');
    const [searchQuery, setSearchQuery] = useState('');
    const [results, setResults] = useState([]);
    const [isSearching, setIsSearching] = useState(false);

    const handleSearch = async () => {
        if (!searchQuery) return;

        setIsSearching(true);

        try {
            const response = await fetch(qcmMetaFields.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'qcm_search_api',
                    nonce: qcmMetaFields.nonce,
                    api_source: apiSource,
                    query: searchQuery,
                }),
            });

            const data = await response.json();

            if (data.success) {
                setResults(data.data.results);
            } else {
                console.error('API search error:', data.data.message);
            }
        } catch (error) {
            console.error('API search error:', error);
        } finally {
            setIsSearching(false);
        }
    };

    const handleSelectItem = (item) => {
        onSelect({
            id: Date.now() + Math.random(),
            title: item.title + (item.year ? ` (${item.year})` : ''),
            image: item.image,
        });
    };

    if (!isOpen) return null;

    return (
        <Modal
            title={__('Search API', 'quick-choice-movies')}
            onRequestClose={onClose}
            className="qcm-api-search-modal"
        >
            <div className="qcm-api-search-modal__controls">
                <SelectControl
                    label={__('API Source', 'quick-choice-movies')}
                    value={apiSource}
                    options={[
                        { label: __('Movies (TMDB)', 'quick-choice-movies'), value: 'tmdb' },
                        { label: __('Video Games (RAWG)', 'quick-choice-movies'), value: 'rawg' },
                        { label: __('Books (Google Books)', 'quick-choice-movies'), value: 'google_books' },
                    ]}
                    onChange={setApiSource}
                />
                <SearchControl
                    label={__('Search', 'quick-choice-movies')}
                    value={searchQuery}
                    onChange={setSearchQuery}
                    onKeyDown={(e) => {
                        if (e.key === 'Enter') {
                            handleSearch();
                        }
                    }}
                />
                <Button
                    variant="primary"
                    onClick={handleSearch}
                    disabled={isSearching || !searchQuery}
                >
                    {isSearching ? <Spinner /> : __('Search', 'quick-choice-movies')}
                </Button>
            </div>

            <div className="qcm-api-search-modal__results">
                {results.length === 0 && !isSearching && (
                    <p>{__('No results yet. Try searching above.', 'quick-choice-movies')}</p>
                )}

                {results.map((item) => (
                    <Card key={item.id} className="qcm-api-result">
                        <CardBody>
                            <div className="qcm-api-result__content">
                                {item.image && (
                                    <img
                                        src={item.image}
                                        alt={item.title}
                                        className="qcm-api-result__image"
                                    />
                                )}
                                <div className="qcm-api-result__info">
                                    <h4>{item.title}</h4>
                                    {item.year && <p>{item.year}</p>}
                                </div>
                            </div>
                        </CardBody>
                        <CardFooter>
                            <Button
                                variant="primary"
                                onClick={() => handleSelectItem(item)}
                            >
                                {__('Add to List', 'quick-choice-movies')}
                            </Button>
                        </CardFooter>
                    </Card>
                ))}
            </div>
        </Modal>
    );
}

// Main Editor Component
export default function MetaFieldsEditor() {
    const [isAPIModalOpen, setIsAPIModalOpen] = useState(false);
    const [items, setItems] = useState([]);

    const sensors = useSensors(
        useSensor(PointerSensor),
        useSensor(KeyboardSensor, {
            coordinateGetter: sortableKeyboardCoordinates,
        })
    );

    // Get current post meta
    const choiceItems = useSelect((select) => {
        const meta = select(editorStore).getEditedPostAttribute('meta');
        return meta?.qcm_choice_items || '';
    }, []);

    const { editPost } = useDispatch(editorStore);

    // Load items from meta on mount
    useEffect(() => {
        if (choiceItems) {
            try {
                const parsed = JSON.parse(choiceItems);
                setItems(Array.isArray(parsed) ? parsed : []);
            } catch (e) {
                setItems([]);
            }
        }
    }, []);

    // Save items to meta whenever they change
    useEffect(() => {
        const timeout = setTimeout(() => {
            editPost({
                meta: {
                    qcm_choice_items: JSON.stringify(items),
                },
            });
        }, 500);

        return () => clearTimeout(timeout);
    }, [items, editPost]);

    const handleAddItem = () => {
        setItems([
            ...items,
            {
                id: Date.now(),
                title: '',
                image: '',
            },
        ]);
    };

    const handleUpdateItem = (index, updatedItem) => {
        const newItems = [...items];
        newItems[index] = updatedItem;
        setItems(newItems);
    };

    const handleRemoveItem = (index) => {
        const newItems = items.filter((_, i) => i !== index);
        setItems(newItems);
    };

    const handleDragEnd = (event) => {
        const { active, over } = event;

        if (active.id !== over.id) {
            setItems((items) => {
                const oldIndex = items.findIndex((item) => item.id === active.id);
                const newIndex = items.findIndex((item) => item.id === over.id);

                return arrayMove(items, oldIndex, newIndex);
            });
        }
    };

    const handleAPISelect = (item) => {
        setItems([...items, item]);
        setIsAPIModalOpen(false);
    };

    return (
        <Panel>
            <PanelBody title={__('Quick Choice Items', 'quick-choice-movies')} initialOpen={true}>
                <PanelRow>
                    <p className="description">
                        {__('Add and manage the items that will appear in your quick choice game.', 'quick-choice-movies')}
                    </p>
                </PanelRow>

                <PanelRow>
                    <Button
                        variant="secondary"
                        onClick={handleAddItem}
                    >
                        {__('Add Manual Item', 'quick-choice-movies')}
                    </Button>
                    <Button
                        variant="secondary"
                        onClick={() => setIsAPIModalOpen(true)}
                    >
                        {__('Search API', 'quick-choice-movies')}
                    </Button>
                </PanelRow>

                <div className="qcm-choice-items">
                    {items.length === 0 ? (
                        <p className="description">
                            {__('No items yet. Add items manually or search using an API.', 'quick-choice-movies')}
                        </p>
                    ) : (
                        <DndContext
                            sensors={sensors}
                            collisionDetection={closestCenter}
                            onDragEnd={handleDragEnd}
                        >
                            <SortableContext
                                items={items.map(item => item.id)}
                                strategy={verticalListSortingStrategy}
                            >
                                {items.map((item, index) => (
                                    <SortableChoiceItem
                                        key={item.id}
                                        item={item}
                                        index={index}
                                        onUpdate={handleUpdateItem}
                                        onRemove={handleRemoveItem}
                                    />
                                ))}
                            </SortableContext>
                        </DndContext>
                    )}
                </div>

                <APISearchModal
                    isOpen={isAPIModalOpen}
                    onClose={() => setIsAPIModalOpen(false)}
                    onSelect={handleAPISelect}
                />
            </PanelBody>
        </Panel>
    );
}
