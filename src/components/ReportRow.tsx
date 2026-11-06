import { FileText, Paperclip } from 'lucide-react-native';
import { StyleSheet, View } from 'react-native';

import { typeLabel } from '@/lib/campus';
import { shortDateTime } from '@/lib/format';
import type { Report } from '@/lib/queries';
import { useTheme } from '@/lib/theme';

import { Badge, IconTile } from './bits';
import { T } from './Text';

export function ReportRow({ r, last }: { r: Report; last?: boolean }) {
  const theme = useTheme();
  return (
    <View style={[styles.row, !last && styles.divider]}>
      <IconTile size={40}>
        <FileText size={18} color={theme.primary} />
      </IconTile>
      <View style={{ flex: 1, gap: 3 }}>
        <View style={styles.top}>
          <T variant="label" numberOfLines={1} style={{ flexShrink: 1 }}>
            {typeLabel(r.type)}
          </T>
          <Badge value={r.status} />
        </View>
        <T variant="small" muted numberOfLines={1}>
          {r.location} · {shortDateTime(r.date_time)}
        </T>
        <T variant="small" numberOfLines={2} style={{ opacity: 0.8 }}>
          {r.description}
        </T>
        <View style={styles.meta}>
          <Badge value={r.severity} />
          {r.has_evidence ? (
            <View style={styles.evidence}>
              <Paperclip size={12} color={theme.muted} />
              <T variant="small" muted style={{ fontSize: 11 }}>
                Evidence
              </T>
            </View>
          ) : null}
        </View>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  row: { flexDirection: 'row', gap: 12, paddingVertical: 13 },
  divider: { borderBottomWidth: StyleSheet.hairlineWidth, borderBottomColor: 'rgba(255,255,255,0.12)' },
  top: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', gap: 8 },
  meta: { flexDirection: 'row', alignItems: 'center', gap: 8, marginTop: 3 },
  evidence: { flexDirection: 'row', alignItems: 'center', gap: 4 },
});
