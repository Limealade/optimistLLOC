/**
 * Registration Form Block
 */
import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl } from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import ServerSideRender from '@wordpress/server-side-render';

// Register the block
registerBlockType('sports-registration/registration-form', {
    title: __('Sports Registration Form', 'sports-registration'),
    description: __('Display a registration form for one of the sports.', 'sports-registration'),
    category: 'widgets',
    icon: 'clipboard',
    supports: {
        html: false,
    },
    attributes: {
        formType: {
            type: 'string',
            default: 'basketball',
        },
    },
    
    edit: function(props) {
        const { attributes, setAttributes } = props;
        const { formType } = attributes;
        const [loading, setLoading] = useState(true);
        const [formTypes, setFormTypes] = useState([
            { label: __('Basketball', 'sports-registration'), value: 'basketball' },
            { label: __('Soccer', 'sports-registration'), value: 'soccer' },
            { label: __('Cheerleading', 'sports-registration'), value: 'cheerleading' },
            { label: __('Volleyball', 'sports-registration'), value: 'volleyball' },
        ]);
        
        // Fetch available form types from server
        useEffect(() => {
            setLoading(false);
        }, []);
        
        return (
            <>
                <InspectorControls>
                    <PanelBody title={__('Form Settings', 'sports-registration')}>
                        <SelectControl
                            label={__('Sport Type', 'sports-registration')}
                            value={formType}
                            options={formTypes}
                            onChange={(value) => setAttributes({ formType: value })}
                        />
                    </PanelBody>
                </InspectorControls>
                
                <div className={`sports-registration-block ${loading ? 'is-loading' : ''}`}>
                    {loading ? (
                        <div className="loading-spinner">
                            <span className="spinner"></span>
                            <p>{__('Loading form...', 'sports-registration')}</p>
                        </div>
                    ) : (
                        <div className="sports-registration-editor-preview">
                            <div className="sports-registration-editor-header">
                                <h3>{__('Sports Registration Form', 'sports-registration')}</h3>
                                <p className="sports-registration-editor-type">
                                    {__('Form Type:', 'sports-registration')} <strong>{formType.charAt(0).toUpperCase() + formType.slice(1)}</strong>
                                </p>
                            </div>
                            <ServerSideRender
                                block="sports-registration/registration-form"
                                attributes={attributes}
                            />
                        </div>
                    )}
                </div>
            </>
        );
    },
    
    save: function() {
        // Dynamic block, server-side rendered
        return null;
    },
});

// Additional styles for the editor
const styles = `
.sports-registration-block {
    border: 1px solid #e2e4e7;
    background-color: #f8f9f9;
    padding: 20px;
    border-radius: 5px;
}

.sports-registration-block.is-loading {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 200px;
}

.sports-registration-block .loading-spinner {
    text-align: center;
}

.sports-registration-block .loading-spinner .spinner {
    float: none;
    margin: 0 auto 10px;
    visibility: visible;
}

.sports-registration-editor-header {
    margin-bottom: 15px;
    padding-bottom: 15px;
    border-bottom: 1px solid #e2e4e7;
}

.sports-registration-editor-header h3 {
    margin-top: 0;
    margin-bottom: 10px;
}

.sports-registration-editor-type {
    margin: 0;
    font-size: 13px;
    color: #555;
}
`;

// Add styles to document
const styleElement = document.createElement('style');
styleElement.innerHTML = styles;
document.head.appendChild(styleElement);
