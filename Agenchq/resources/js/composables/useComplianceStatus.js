import { computed } from 'vue';

export function getCalculatedStatusFromExpiry(expiryDate) {
    if (!expiryDate) {
        return { status: 'pending', color: 'gray' };
    }

    const today = new Date();
    today.setHours(0, 0, 0, 0);

    const expiry = new Date(String(expiryDate));
    if (Number.isNaN(expiry.getTime())) {
        return { status: 'pending', color: 'gray' };
    }
    expiry.setHours(0, 0, 0, 0);

    const msPerDay = 1000 * 60 * 60 * 24;
    const daysUntilExpiry = Math.floor((expiry.getTime() - today.getTime()) / msPerDay);

    if (expiry.getTime() <= today.getTime()) {
        return { status: 'expired', color: 'red' };
    }

    if (daysUntilExpiry <= 30) {
        return { status: 'expiring_soon', color: 'yellow' };
    }

    return { status: 'active', color: 'green' };
}

export function useComplianceStatus(expiryDateRef) {
    return computed(() => getCalculatedStatusFromExpiry(expiryDateRef?.value));
}
