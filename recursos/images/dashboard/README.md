# Imagens do Dashboard Hero - Usuários Logados

## Instruções

Este diretório contém as imagens do carrossel do hero para a sessão do usuário logado (dashboard).

## Imagens Necessárias

Salve as 7 imagens que você forneceu neste diretório com os seguintes nomes:

| # | Nome do Arquivo | Descrição |
|---|---|---|
| 1 | `hero_team_discussion.jpg` | Imagem 1: Discussão em Equipa (KALIYE) |
| 2 | `hero_business_presentation.jpg` | Imagem 2: Apresentação de Negócios |
| 3 | `hero_workplace_collaboration.jpg` | Imagem 3: Colaboração no Workplace |
| 4 | `hero_team_handshake.jpg` | Imagem 4: Aperto de Mão / Partnership |
| 5 | `hero_women_working.jpg` | Imagem 5: Mulheres Trabalhando |
| 6 | `hero_team_meeting.jpg` | Imagem 6: Reunião de Equipa |
| 7 | `hero_startup_office.jpg` | Imagem 7: Startup Office |

## Como Adicionar as Imagens

1. **Via FTP/File Manager:**
   - Conecte-se ao servidor via FTP
   - Navegue até `recursos/images/dashboard/`
   - Faça upload das 7 imagens com os nomes exatos acima

2. **Localmente (Desenvolvimento):**
   ```bash
   cd recursos/images/dashboard/
   # Copie as 7 imagens aqui com os nomes corretos
   ```

## Especificações Recomendadas

- **Formato:** JPG ou PNG
- **Resolução:** 1920x1200px ou superior
- **Tamanho máximo:** 500KB por imagem
- **Aspect Ratio:** 16:10 ou 1.6:1

## Validação

Após adicionar as imagens:

1. Aceda ao dashboard (`/index.php`) como usuário logado
2. Verifique se o carrossel de imagens está funcionando
3. As imagens devem fazer scroll automaticamente

## Imagens da Landing Page (Usuários Não Logados)

As imagens antigas continuam em:
- `recursos/images/slide1.png`
- `recursos/images/slide2.png`
- `recursos/images/slide3.png`
- `recursos/images/slide4.jpg`
- `recursos/images/slide5.jpg`
- `recursos/images/slide6.jpg`

Estas são usadas em `inclusoes/components/landing_hero.php` para a página inicial (usuários não logados).

## Notas

- O carrossel tem loop infinito automático
- As imagens têm efeito skew CSS (-6deg)
- Todas as imagens recebem um filtro de saturação e contraste
- A opacidade padrão é 0.22 para não ofuscar o conteúdo

---
**Última atualização:** 1 de junho de 2026
**Status:** ✅ Sistema de dashboar hero atualizado
