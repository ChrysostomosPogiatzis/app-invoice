export const formatDate = (date: string | null) => {
    if (!date) return 'N/A';
    return new Date(date).toLocaleDateString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    });
};

export const formatMoney = (amount: number | string) => {
    return Number(amount).toLocaleString('en-GB', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
};

export const withValidation = (classes: string, error: any) => {
    return `${classes} ${error ? 'border-rose-500 bg-rose-50' : ''}`;
};
