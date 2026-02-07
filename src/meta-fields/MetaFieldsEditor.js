import ItemManager from '../shared/components/ItemManager';

export default function MetaFieldsEditor() {
    return (
        <ItemManager
            ajaxUrl={qcmMetaFields.ajaxUrl}
            saveNonce={qcmMetaFields.nonce}
            searchNonce={qcmMetaFields.searchNonce}
            saveAction="qcm_save_items"
            postId={qcmMetaFields.postId}
            initialItemsJSON={qcmMetaFields.currentMeta}
            panelTitle="Quick Choice Items"
        />
    );
}
