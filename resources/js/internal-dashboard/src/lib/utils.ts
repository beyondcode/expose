import { type ClassValue, clsx } from 'clsx'
import { twMerge } from 'tailwind-merge'
import { useClipboard } from '@vueuse/core'

export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs))
}

export function isEmptyObject(obj: object): boolean {
    return Object.keys(obj).length === 0
}

export function copyToClipboard(source: string): void {
    const { copy } = useClipboard({ source })
    copy()
}

export function toPhpArray(rows: Record<string, string>, variableName: string): string {
    let output = `$${variableName} = [\n`;

    for (let key in rows) {
        let value = rows[key];

        if (typeof value.value !== 'undefined') {
            value = value.value;
        }

        output += `    '${key}' => '${value}',\n`;
    }

    output += `];`;

    return output;
}

export function bodyIsJson(payload: ResponseData | RequestData): boolean {
    if (!payload || !payload.headers || payload.headers['Content-Type'] === null) {
        return false;
    }

    const contentType = payload.headers['Content-Type'];
    let hasContentType = contentType ? /application\/json/g.test(contentType) : false;
    try {
        if (payload.body) {
            JSON.parse(payload.body);
        }
        return hasContentType;
    } catch (e) {
        return false;
    }
}