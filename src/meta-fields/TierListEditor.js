import { useState, useEffect, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
    Button,
    Panel,
    PanelBody,
    PanelRow,
    TextControl,
    Notice,
} from '@wordpress/components';
import { trash, plus } from '@wordpress/icons';
import ItemManager from '../shared/components/ItemManager';

export default function TierListEditor() {
    const [tiers, setTiersRaw] = useState([]);
    const [saveStatus, setSaveStatus] = useState('');
    const [saveMessage, setSaveMessage] = useState('');
    const [initialized, setInitialized] = useState(false);
    const itemsRef = useRef([]);

    // Wrapper: always assign IDs based on position
    const setTiers = (newTiers) => {
        const normalized = (Array.isArray(newTiers) ? newTiers : []).map((tier, index) => ({
            ...tier,
            id: String(index),
        }));
        setTiersRaw(normalized);
    };

    // Load initial tiers
    useEffect(() => {
        if (qcmTierListMeta?.currentTiers) {
            try {
                const parsed = JSON.parse(qcmTierListMeta.currentTiers);
                if (Array.isArray(parsed)) {
                    setTiers(parsed);
                }
            } catch (e) {
                console.error('Error parsing tiers:', e);
                setTiers(qcmTierListMeta.defaultTiers || []);
            }
        } else {
            setTiers(qcmTierListMeta?.defaultTiers || []);
        }
        setInitialized(true);
    }, []);

    // Auto-save tiers when they change
    useEffect(() => {
        if (!initialized) return;

        const timeout = setTimeout(() => {
            saveTierList(itemsRef.current, tiers);
        }, 2000);

        return () => clearTimeout(timeout);
    }, [tiers, initialized]);

    const saveTierList = async (items, tiersToSave) => {
        setSaveStatus('saving');
        setSaveMessage('Guardando...');

        try {
            const formData = new URLSearchParams();
            formData.append('action', 'qcm_save_tier_list');
            formData.append('nonce', qcmTierListMeta.nonce);
            formData.append('post_id', qcmTierListMeta.postId);
            formData.append('items', JSON.stringify(items));
            formData.append('tiers', JSON.stringify(tiersToSave));

            const response = await fetch(qcmTierListMeta.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: formData.toString(),
            });

            const data = await response.json();

            if (data.success) {
                setSaveStatus('success');
                setSaveMessage(__('Saved successfully', 'quick-choice-movies'));
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

    const handleUpdateTierLabel = (index, label) => {
        const newTiers = [...tiers];
        newTiers[index] = { ...newTiers[index], label };
        setTiers(newTiers);
    };

    const handleUpdateTierColor = (index, color) => {
        const newTiers = [...tiers];
        newTiers[index] = { ...newTiers[index], color };
        setTiers(newTiers);
    };

    const handleRemoveTier = (index) => {
        setTiers(tiers.filter((_, i) => i !== index));
    };

    const handleAddTier = () => {
        const label = String.fromCharCode(65 + tiers.length); // A, B, C... for display only
        setTiers([...tiers, { id: '', label, color: '#cccccc' }]);
    };

    const handleResetTiers = () => {
        setTiers(qcmTierListMeta?.defaultTiers || []);
    };

    // Build custom save body that includes tiers
    const buildSaveBody = (items, postId, nonce) => {
        const formData = new URLSearchParams();
        formData.append('action', 'qcm_save_tier_list');
        formData.append('nonce', qcmTierListMeta.nonce);
        formData.append('post_id', postId);
        formData.append('items', JSON.stringify(items));
        formData.append('tiers', JSON.stringify(tiers));
        return formData.toString();
    };

    const handleItemsChange = (items) => {
        itemsRef.current = items;
    };

    return (
        <div className="qcm-tier-list-editor">
            <Panel>
                <PanelBody title={__('Tier Configuration', 'quick-choice-movies')} initialOpen={true}>
                    {saveStatus === 'saving' && (
                        <Notice status="info" isDismissible={false}>{saveMessage}</Notice>
                    )}
                    {saveStatus === 'success' && (
                        <Notice status="success" isDismissible={false}>{saveMessage}</Notice>
                    )}
                    {saveStatus === 'error' && (
                        <Notice status="error" isDismissible={false}>{saveMessage}</Notice>
                    )}

                    <div className="qcm-tiers-config">
                        {tiers.map((tier, index) => (
                            <div
                                key={index}
                                className="qcm-tier-config"
                                style={{ borderLeftColor: tier.color }}
                            >
                                <input
                                    type="color"
                                    className="qcm-tier-config__color"
                                    value={tier.color}
                                    onChange={(e) => handleUpdateTierColor(index, e.target.value)}
                                />
                                <div className="qcm-tier-config__label">
                                    <TextControl
                                        value={tier.label}
                                        onChange={(value) => handleUpdateTierLabel(index, value)}
                                    />
                                </div>
                                <Button
                                    icon={trash}
                                    variant="tertiary"
                                    isDestructive
                                    onClick={() => handleRemoveTier(index)}
                                />
                            </div>
                        ))}
                    </div>

                    <PanelRow>
                        <Button
                            variant="secondary"
                            icon={plus}
                            onClick={handleAddTier}
                        >
                            {__('Add Tier', 'quick-choice-movies')}
                        </Button>
                        <Button
                            variant="tertiary"
                            onClick={handleResetTiers}
                        >
                            {__('Reset to Default', 'quick-choice-movies')}
                        </Button>
                    </PanelRow>
                </PanelBody>
            </Panel>

            <ItemManager
                ajaxUrl={qcmTierListMeta.ajaxUrl}
                saveNonce={qcmTierListMeta.nonce}
                searchNonce={qcmTierListMeta.searchNonce}
                saveAction="qcm_save_tier_list"
                postId={qcmTierListMeta.postId}
                initialItemsJSON={qcmTierListMeta.currentItems}
                panelTitle={__('Tier List Items', 'quick-choice-movies')}
                onItemsChange={handleItemsChange}
                buildSaveBody={buildSaveBody}
            />
        </div>
    );
}
