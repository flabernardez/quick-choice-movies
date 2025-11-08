import { render } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import MetaFieldsEditor from './MetaFieldsEditor';
import './editor.scss';

// Wait for DOM to be ready
document.addEventListener('DOMContentLoaded', () => {
    const metaBoxContainer = document.getElementById('qcm-meta-fields-root');

    if (metaBoxContainer) {
        render(<MetaFieldsEditor />, metaBoxContainer);
    }
});
