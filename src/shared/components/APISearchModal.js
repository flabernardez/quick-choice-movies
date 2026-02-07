import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
    Button,
    SelectControl,
    Spinner,
    Card,
    CardBody,
    CardFooter,
    Modal,
    SearchControl,
} from '@wordpress/components';

const LANGUAGE_OPTIONS = [
    { label: 'English', value: 'en' },
    { label: 'Español', value: 'es' },
    { label: 'Français', value: 'fr' },
    { label: 'Deutsch', value: 'de' },
    { label: 'Italiano', value: 'it' },
    { label: 'Português', value: 'pt' },
    { label: '日本語', value: 'ja' },
    { label: '한국어', value: 'ko' },
    { label: '中文', value: 'zh' },
];

/**
 * API Search Modal Component
 *
 * @param {Object} props
 * @param {boolean} props.isOpen
 * @param {Function} props.onClose
 * @param {Function} props.onSelect - Called with { id, title, image } when user picks an item
 * @param {string} props.ajaxUrl
 * @param {string} props.searchNonce - Nonce for qcm_api_search action
 */
export default function APISearchModal({ isOpen, onClose, onSelect, ajaxUrl, searchNonce }) {
    const [apiSource, setApiSource] = useState('tmdb');
    const [searchQuery, setSearchQuery] = useState('');
    const [language, setLanguage] = useState('es');
    const [results, setResults] = useState([]);
    const [isSearching, setIsSearching] = useState(false);

    const handleSearch = async () => {
        if (!searchQuery) return;

        setIsSearching(true);

        try {
            const params = {
                action: 'qcm_api_search',
                nonce: searchNonce,
                api_source: apiSource,
                query: searchQuery,
            };

            if (language) {
                params.language = language;
            }

            const response = await fetch(ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams(params),
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
                <div className="qcm-api-search-modal__row">
                    <SelectControl
                        label={__('API Source', 'quick-choice-movies')}
                        value={apiSource}
                        options={[
                            { label: __('Movies (TMDB)', 'quick-choice-movies'), value: 'tmdb' },
                            { label: __('Video Games (RAWG)', 'quick-choice-movies'), value: 'rawg' },
                            { label: __('Books (Open Library)', 'quick-choice-movies'), value: 'openlibrary' },
                        ]}
                        onChange={setApiSource}
                    />
                    <SelectControl
                        label={__('Language', 'quick-choice-movies')}
                        value={language}
                        options={LANGUAGE_OPTIONS}
                        onChange={setLanguage}
                    />
                </div>
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
                {apiSource === 'tmdb' && (
                    <p className="description" style={{ fontSize: '12px', marginTop: '-10px' }}>
                        {__('Try: "robert redford", "spielberg director", movie title, etc.', 'quick-choice-movies')}
                    </p>
                )}
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
