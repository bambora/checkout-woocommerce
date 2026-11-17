const { createElement, useState, useEffect } = window.wp.element;

const PAYMENT_METHOD_ID = 'bambora_online_checkout';

/**
 * The content shown when the payment method is selected:
 * the logos of the payment types in the payment window, followed by the description from the module configuration.
 */
const PaymentDescriptionWithCardLogos = (props) => {
    const [logos, setLogos] = useState([]);
    const fetchLogos = async () => {
        try {
            const response = await fetch('?rest_route=/bambora/v1/paymenttypes/', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    amount: props.billing.cartTotal.value,
                    currency: props.billing.currency.code,
                })
            });
            if (!response.ok) {
                console.warn('Failed to fetch payment logos. Status:', response.status);
                return;
            }
            const logoData = await response.json();
            const logoDataMap = logoData.flat()
                .map(item => ({
                    name: item.displayname,
                    url: item.assets.find(asset => asset.type === 'logo')?.data
                }))
                .filter(logo => logo.url);
            setLogos(logoDataMap);
        } catch (error) {
            console.error(`Error loading logos for ${PAYMENT_METHOD_ID}:`, error);
        }
    };

    useEffect(() => {
        fetchLogos();
    }, [props.billing.cartTotal.value, props.billing.currency.code]);

    const children = [];
    if (logos.length > 0) {
        children.push(
            createElement(
                'div',
                { key: 'logos', className: 'bambora_payment_types' },
                ...logos.map((logo, i) =>
                    createElement('img', {
                        key: i,
                        src: logo.url,
                        title: logo.name,
                        alt: logo.name,
                    })
                )
            )
        );
    }
    if (props.description) {
        children.push(
            createElement('p', { key: 'description', className: 'bambora_payment_text' }, props.description)
        );
    }
    return createElement('div', { className: 'bambora_payment_description' }, ...children);
};

/**
 * The payment method label: the title from the module configuration with a small Worldline logo on the right.
 */
const LabelWithIcon = ({ label, icon }) => {
    const children = [
        createElement('span', { key: 'label', className: 'bambora_payment_label' }, label)
    ];
    if (icon) {
        children.push(
            createElement('img', {
                key: 'icon',
                src: icon,
                alt: 'Worldline',
                className: 'bambora_payment_icon'
            })
        );
    }
    return createElement('span', { className: 'bambora_payment_label_icon' }, ...children);
};

const buildCustomPaymentMethod = (config) => ({
    name: config.id || 'bambora',
    ariaLabel: config.ariaLabel || config.label || 'Worldline Online Checkout',
    supports: {
        features: config.supports
    },
    canMakePayment: () => true,
    label: createElement(LabelWithIcon, {
        label: config.label || 'Worldline Online Checkout',
        icon: config.icon
    }),
    edit: createElement(LabelWithIcon, {
        label: config.label || 'Worldline Online Checkout',
        icon: config.icon
    }),
    content: createElement(PaymentDescriptionWithCardLogos, {
        description: config.description,
    }),
});

(async () => {
    const registry = window.wc?.wcBlocksRegistry;
    const settings = window.wc?.wcSettings;

    if (!registry?.registerPaymentMethod || !settings?.getSetting(`${PAYMENT_METHOD_ID}_data`)) return;

    const methodConfig = settings.getSetting(`${PAYMENT_METHOD_ID}_data`);
    registry.registerPaymentMethod(buildCustomPaymentMethod(methodConfig));
})();
