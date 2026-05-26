=== Novacast ===
Contributors: henrkecrz
Tags: podcast, audio, player, shortcode, youtube, spotify
Requires at least: 6.0
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 0.2.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Gerencie episódios de podcast no WordPress e exiba players no frontend.

== Description ==

Novacast cria um painel de episódios de podcast dentro do WordPress. Cada episódio pode ter título, descrição, capa, URL de áudio, duração, fonte de reprodução e status de exibição.

O plugin oferece o shortcode [novacast_player] para exibir episódios no frontend com player responsivo.

Fontes de reprodução disponíveis:

* Áudio próprio via player HTML5.
* YouTube via embed oficial.
* Spotify via embed oficial.

A sincronização com YouTube e Spotify importa metadados públicos e cria/atualiza episódios no WordPress. O plugin não baixa áudio dessas plataformas.

== Installation ==

1. Envie a pasta Novacast para wp-content/plugins/.
2. Ative o plugin no painel do WordPress.
3. Acesse Novacast > Adicionar novo para cadastrar episódios manualmente.
4. Acesse Novacast > Sincronização para configurar YouTube e Spotify.
5. Use o shortcode [novacast_player] em uma página ou post.

== Shortcodes ==

Exibir os últimos episódios:

[novacast_player]

Exibir até 5 episódios:

[novacast_player limit="5"]

Exibir um episódio específico:

[novacast_player id="123"]

== YouTube ==

Para sincronizar YouTube, informe uma YouTube API Key e o ID de uma playlist pública em Novacast > Sincronização.

Os episódios importados usam o embed oficial do YouTube no frontend.

== Spotify ==

Para sincronizar Spotify, informe Client ID, Client Secret e o ID ou URL de um show em Novacast > Sincronização.

Os episódios importados usam o embed oficial do Spotify no frontend.

== Changelog ==

= 0.2.2 =
* Novo visual premium para o frontend do player.
* Cabeçalho com identidade azul e branca da Novacap.
* Cards de episódios com visual mais refinado.
* Selo de origem do episódio e destaque "Ouça agora".

= 0.2.1 =
* Atualizada a versão para facilitar substituição/atualização pelo painel do WordPress.
* Mantidos os ajustes visuais da seção Novacast no frontend.

= 0.2.0 =
* Adicionada seleção de fonte de reprodução por episódio.
* Adicionado suporte a YouTube Embed no frontend.
* Adicionado suporte a Spotify Embed no frontend.
* Adicionada tela Novacast > Sincronização.
* Adicionada sincronização manual com playlist do YouTube.
* Adicionada sincronização manual com show do Spotify.

= 0.1.0 =
* Estrutura inicial do plugin.
* Cadastro de episódios como Custom Post Type.
* Campos de URL de áudio, duração e status ativo.
* Shortcode de player no frontend.
