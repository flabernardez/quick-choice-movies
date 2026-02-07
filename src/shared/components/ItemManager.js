import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
    Button,
    Panel,
    PanelBody,
    PanelRow,
    Notice,
} from '@wordpress/components';
import { DndContext, closestCenter, KeyboardSensor, PointerSensor, useSensor, useSensors } from '@dnd-kit/core';
import { arrayMove, SortableContext, sortableKeyboardCoordinates, verticalListSortingStrategy } from '@dnd-kit/sortable';
import SortableChoiceItem from './SortableChoiceItem';
import APISearchModal from './APISearchModal';

/**
 * ItemManager — reusable item list manager with drag-and-drop, manual add, API search, and auto-save.
 *
 * @param {Object} props
 * @param {string} props.ajaxUrl
 * @param {string} props.saveNonce - Nonce for the save AJAX action
 * @param {string} props.searchNonce - Nonce for qcm_api_search
 * @param {string} props.saveAction - AJAX action name for saving (e.g. 'qcm_save_items')
 * @param {number} props.postId
 * @param {string} props.initialItemsJSON - Initial items as a JSON string
 * @param {string} [props.panelTitle] - Panel title
 * @param {Function} [props.onItemsChange] - Called with the items array whenever it changes
 * @param {Function} [props.buildSaveBody] - Custom function to build the save request body. Receives (items, postId, nonce). If not provided, uses default.
 */
export default function ItemManager({
    ajaxUrl,
    saveNonce,
    searchNonce,
    saveAction,
    postId,
    initialItemsJSON,
    panelTitle,
    onItemsChange,
    buildSaveBody,
}) {
    const [isAPIModalOpen, setIsAPIModalOpen] = useState(false);
    const [items, setItems] = useState([]);
    const [saveStatus, setSaveStatus] = useState('');
    const [saveMessage, setSaveMessage] = useState('');
    const [initialized, setInitialized] = useState(false);

    const sensors = useSensors(
        useSensor(PointerSensor),
        useSensor(KeyboardSensor, {
            coordinateGetter: sortableKeyboardCoordinates,
        })
    );

    // Load initial data
    useEffect(() => {
        if (initialItemsJSON) {
            try {
                const parsed = JSON.parse(initialItemsJSON);
                if (Array.isArray(parsed)) {
                    setItems(parsed);
                }
            } catch (e) {
                console.error('Error parsing items:', e);
            }
        }
        setInitialized(true);
    }, []);

    // Notify parent of changes
    useEffect(() => {
        if (onItemsChange) {
            onItemsChange(items);
        }
    }, [items]);

    // Save function using AJAX
    const saveItems = async (itemsToSave) => {
        setSaveStatus('saving');
        setSaveMessage('Guardando...');

        const jsonString = JSON.stringify(itemsToSave);

        try {
            let body;
            if (buildSaveBody) {
                body = buildSaveBody(itemsToSave, postId, saveNonce);
            } else {
                const formData = new URLSearchParams();
                formData.append('action', saveAction);
                formData.append('nonce', saveNonce);
                formData.append('post_id', postId);
                formData.append('items', jsonString);
                body = formData.toString();
            }

            const response = await fetch(ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body,
            });

            const data = await response.json();

            if (data.success) {
                setSaveStatus('success');
                setSaveMessage(`✓ ${itemsToSave.length} items guardados`);

                setTimeout(() => {
                    setSaveStatus('');
                    setSaveMessage('');
                }, 3000);
            } else {
                setSaveStatus('error');
                setSaveMessage('Error: ' + (data.data?.message || 'Unknown error'));
            }
        } catch (error) {
            setSaveStatus('error');
            setSaveMessage('Error de red');
            console.error('Network error:', error);
        }
    };

    // Auto-save when items change (skip initial load)
    useEffect(() => {
        if (!initialized || items.length === 0) return;

        const timeout = setTimeout(() => {
            saveItems(items);
        }, 2000);

        return () => clearTimeout(timeout);
    }, [items, initialized]);

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

    const handleManualSave = () => {
        saveItems(items);
    };

    return (
        <Panel>
            <PanelBody title={panelTitle || __('Items', 'quick-choice-movies')} initialOpen={true}>
                {saveStatus === 'saving' && (
                    <Notice status="info" isDismissible={false}>
                        {saveMessage}
                    </Notice>
                )}
                {saveStatus === 'success' && (
                    <Notice status="success" isDismissible={false}>
                        {saveMessage}
                    </Notice>
                )}
                {saveStatus === 'error' && (
                    <Notice status="error" isDismissible={false}>
                        {saveMessage}
                    </Notice>
                )}

                <PanelRow>
                    <p className="description">
                        <strong>{items.length}</strong> {__('items', 'quick-choice-movies')}
                        {' • '}
                        {__('Se guarda automáticamente', 'quick-choice-movies')}
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
                    <Button
                        variant="primary"
                        onClick={handleManualSave}
                        disabled={items.length === 0}
                    >
                        {__('Guardar Ahora', 'quick-choice-movies')}
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
                    ajaxUrl={ajaxUrl}
                    searchNonce={searchNonce}
                />
            </PanelBody>
        </Panel>
    );
}
