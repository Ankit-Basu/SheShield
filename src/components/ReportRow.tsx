import { Paperclip } from 'lucide-react-native';
import { StyleSheet, View } from 'react-native';

import { typeLabel } from '@/lib/campus';
import { shortDateTime } from '@/lib/format';
import type { Report } from '@/lib/queries';
import { status, useTheme } from '@/lib/theme';

import { Badge } from './bits';
import { T } from './Text';

const severityColor = { high: '#ff6b6b', medium: status.warn, low: status.info } as const;

// One report as a list row: a severity dot, the type and its status, then where and
// when, then the first line of what happened.
export function ReportRow({ r, last }: { r: Report; last?: boolean }) {
  const theme = useTheme();
  return (
    <View style={[styles.row, !last && styles.divider]} accessibilityLabel={`${typeLabel(r.type)}, ${r.severity} severity, ${r.status.replace('_', ' ')}`}>
      <View style={[styles.dot, { backgroundColor: severityColor[r.severity as keyof typeof severityColor] ?? theme.muted }]} />
      <View style={{ flex: 1, gap: 3 }}>
        <View style={styles.top}>
          <T variant="label" numberOfLines={1} style={{ flexShrink: 1 }}>
            {typeLabel(r.type)}
          </T>
          <Badge value={r.status} />
        </View>
        <View style={styles.meta}>
          <T variant="small" muted numberOfLines={1} style={{ flexShrink: 1 }}>
            {r.location} · {shortDateTime(r.date_time)}
          </T>
          {r.has_evidence ? <Paperclip size={12} color={theme.muted} /> : null}
        </View>
        <T variant="small" numberOfLines={1} style={{ opacity: 0.75 }}>
          {r.description}
        </T>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  row: { flexDirection: 'row', gap: 12, paddingVertical: 14 },
  divider: { borderBottomWidth: StyleSheet.hairlineWidth, borderBottomColor: 'rgba(255,255,255,0.12)' },
  dot: { width: 8, height: 8, borderRadius: 4, marginTop: 6 },
  top: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', gap: 8 },
  meta: { flexDirection: 'row', alignItems: 'center', gap: 6 },
});
