import { render } from '@wordpress/element';
import TierListEditor from './TierListEditor';
import './editor.scss';

document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('qcm-tier-list-meta-root');

    if (container) {
        render(<TierListEditor />, container);
    }
});
