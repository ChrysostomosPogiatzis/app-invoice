export const withValidation = (baseClasses: string, error?: string) =>
    `${baseClasses} ${error ? 'border-rose-300 bg-rose-50/60 text-rose-900 placeholder-rose-300 focus:border-rose-500 focus:ring-rose-500/20' : ''}`.trim();

