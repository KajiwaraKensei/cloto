import { zeroPadding } from '@/utils';

export function getTime(date) {
  const twoLength = zeroPadding(2);
  const _date = new Date(date);
  return `${twoLength(_date.getHours())}:${twoLength(_date.getMinutes())}`;
}
