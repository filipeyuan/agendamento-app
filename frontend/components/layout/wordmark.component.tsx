export function Wordmark({ inverted = false }: { inverted?: boolean }) {
  // Precisa ser UM elemento só (não um Fragment com filhos soltos): o lugar
  // onde isso é usado é sempre dentro de um container flex com gap, e um
  // Fragment expõe "Z" e "elo" como dois itens do flex separados - o gap
  // do container entrava entre eles e quebrava a palavra ao meio.
  return (
    <span>
      {/* "inverted" é pra usar em cima de um fundo já colorido com --primary
          (ex: faixa do drawer mobile) - reaproveitar --primary pro Z ali
          ficaria ilegível, então cai pra a cor de texto herdada. */}
      <span style={inverted ? undefined : { color: "var(--primary)" }}>Z</span>
      elo
    </span>
  );
}
