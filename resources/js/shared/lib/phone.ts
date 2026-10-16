// Bangladeshi mobile number: 01X followed by 8 digits.
const BD_MOBILE_PATTERN = /^01[3-9]\d{8}$/;

export function isBangladeshiMobile(value: string): boolean {
    return BD_MOBILE_PATTERN.test(value.replace(/\s+/g, ''));
}
