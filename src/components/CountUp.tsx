import { useEffect, useState } from 'react';
import { TextStyle } from 'react-native';

import { T } from './Text';

// Counts up from zero when the number arrives, eased, once.
export function CountUp({ value, style, color }: { value: number; style?: TextStyle; color?: string }) {
  const [shown, setShown] = useState(0);
  useEffect(() => {
    let raf = 0;
    const start = Date.now();
    const duration = Math.min(900, 300 + value * 60);
    const tick = () => {
      const p = Math.min(1, (Date.now() - start) / duration);
      setShown(Math.round(value * (1 - Math.pow(1 - p, 3))));
      if (p < 1) raf = requestAnimationFrame(tick);
    };
    tick();
    return () => cancelAnimationFrame(raf);
  }, [value]);
  return (
    <T variant="display" style={[{ fontSize: 34, lineHeight: 38 }, style]} color={color}>
      {shown}
    </T>
  );
}
