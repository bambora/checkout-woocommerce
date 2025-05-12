const { createElement, useState, useEffect } = window.wp.element;

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
                return null;
            }
            const logoData = await response.json();
            const logoDataMap = logoData.flat().map(item => ({
                name: item.displayname,
                url: item.assets.find(asset => asset.type === 'logo')?.data
            }));
            if (Array.isArray(logoDataMap)) {
                setLogos(logoDataMap);
            }
        } catch (error) {
            console.error(`Error loading logos for ${paymentMethodId}:`, error);
        }
    };

    useEffect(() => {
        fetchLogos();
    }, [props.billing.cartTotal.value, props.billing.currency.code]);

    const children = [];
    if (props.description) {
        children.push(
            createElement('span', null, props.description)
        );
    }
    if (logos.length > 0) {
        const logoElements = logos.map((logo, i) =>
            createElement('img', {
                key: i,
                src: logo.url,
                title: logo.name,
                alt: `${logo.name} logo ${i + 1}`,
            })
        );
        children.push(
            createElement(
                'div',
                { class: 'bambora_payment_types' },
                ...logoElements
            )
        );
    }
    return createElement('div', { class: 'bambora_payment_description' }, ...children);
};

const LabelWithIcon = ({ label, icon }) => {
    const children = [];

    children.push(
        createElement('strong', null, label)
    );

    children.push(
        createElement('img', {
            key: 0,
            src: icon,
            title: label,
            alt: `${icon} logo`,
            class: 'bambora_payment_icon'
        })
    );
    return createElement('div', { class: 'bambora_payment_label_icon' }, ...children);
}

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

    const targetMethodId = 'bambora_online_checkout';

    if (!registry?.registerPaymentMethod || !settings?.getSetting(`${targetMethodId}_data`)) return;

    const methodConfig = settings.getSetting(`${targetMethodId}_data`);
    const customMethod = buildCustomPaymentMethod(methodConfig);

    registry.registerPaymentMethod(customMethod);
})();