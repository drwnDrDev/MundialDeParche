export default function Locked({ roundName, roundOrder, isLocked, opensAt }) {
    return (
        <div>
            <h1>{roundName}</h1>
            <p>{isLocked ? 'Esta ronda está cerrada.' : 'Esta ronda aún no está abierta.'}</p>
            {opensAt && <p>Abre: {opensAt}</p>}
        </div>
    );
}
