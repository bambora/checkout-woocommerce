const bambora_settings = window.wc.wcSettings.getSetting('Bambora_Online_Checkout_data', {});
const bambora_label = window.wp.htmlEntities.decodeEntities(bambora_settings.title) || window.wp.i18n.__('Worldline Checkout', 'Worldline Checkout');

const BamboraIcon = () => {
    return bambora_settings.icon
        ? React.createElement('img', {
            src: bambora_settings.icon,
            style: {marginLeft: '20px'},
            alt: 'Worldline',
            width: '100',
            height: '24'
        })
        : null;
};

const BamboraLabel = () => {
    return React.createElement(
        'span',
        {style: {display: 'flex', alignItems: 'center'}},
        React.createElement('strong', null, bambora_label),
        React.createElement(BamboraIcon, null)
    );
};

const Bambora_Content = () => {
    return React.createElement('div', {dangerouslySetInnerHTML: {__html: bambora_settings.description || ''}});
};

const Bambora_Block_Gateway = {
    name: 'bambora',
    label: React.createElement(BamboraLabel, null),
    content: React.createElement(Bambora_Content, null),
    edit: React.createElement(Bambora_Content, null),
    canMakePayment: () => true,
    ariaLabel: bambora_label,
    supports: {
        features: bambora_settings.supports,
    },
};

window.wc.wcBlocksRegistry.registerPaymentMethod(Bambora_Block_Gateway);