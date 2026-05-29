import PtsChip from '@/Components/ui/PtsChip';

export default function ReceiptMatchRow({ fixture, prediction, isFinalized }) {
    const homeCode = fixture.home_team?.fifa_code ?? fixture.home_placeholder ?? 'TBD';
    const awayCode = fixture.away_team?.fifa_code ?? fixture.away_placeholder ?? 'TBD';
    const homeFlag = fixture.home_team?.flag_url;
    const awayFlag = fixture.away_team?.flag_url;

    const realScore = fixture.home_score !== null && fixture.away_score !== null
        ? `${fixture.home_score}–${fixture.away_score}`
        : '–';

    const predScore = prediction
        ? `${prediction.predicted_home}–${prediction.predicted_away}`
        : '—';

    return (
        <div className="flex items-center gap-2 px-[18px] py-2.5 border-b border-ink/10">
            {/* Resultado real */}
            <div className="flex items-center gap-1 flex-1 min-w-0 overflow-hidden">
                {homeFlag
                    ? <img src={homeFlag} className="w-5 h-3.5 object-cover border border-ink/20 flex-shrink-0" alt={homeCode} />
                    : <span className="w-5 h-3.5 bg-ink/10 border border-ink/20 flex-shrink-0" />
                }
                <span className="font-mono text-[10px] font-bold truncate">{homeCode}</span>
                <span className="font-display text-[13px] mx-1 flex-shrink-0">{realScore}</span>
                <span className="font-mono text-[10px] font-bold truncate">{awayCode}</span>
                {awayFlag
                    ? <img src={awayFlag} className="w-5 h-3.5 object-cover border border-ink/20 flex-shrink-0" alt={awayCode} />
                    : <span className="w-5 h-3.5 bg-ink/10 border border-ink/20 flex-shrink-0" />
                }
            </div>

            {/* Predicción + chips */}
            <div className="flex items-center gap-1.5 flex-shrink-0">
                <span className="font-mono text-[10px] opacity-40">→</span>
                <span className="font-mono text-[12px] font-bold">{predScore}</span>

                {isFinalized && prediction && (
                    <div className="flex gap-1">
                        <PtsChip pts={prediction.pts_exact}  type="exact"  />
                        <PtsChip pts={prediction.pts_result} type="result" />
                    </div>
                )}
            </div>
        </div>
    );
}
