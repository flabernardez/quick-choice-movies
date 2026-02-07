import { render } from '@wordpress/element';
import MetaFieldsEditor from './MetaFieldsEditor';
import TierListEditor from './TierListEditor';
import './editor.scss';

// Wait for DOM to be ready
document.addEventListener('DOMContentLoaded', () => {
    const metaBoxContainer = document.getElementById('qcm-meta-fields-root');
    if (metaBoxContainer) {
        render(<MetaFieldsEditor />, metaBoxContainer);
    }

    const tierListContainer = document.getElementById('qcm-tier-list-meta-root');
    if (tierListContainer) {
        render(<TierListEditor />, tierListContainer);
    }
});
