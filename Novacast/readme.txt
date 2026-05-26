=== Novacast ===
Contributors: henrkecrz
Tags: podcast, audio, player, shortcode
Requires at least: 6.0
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Gerencie episódios de podcast no WordPress e exiba players no frontend.

== Description ==

Novacast cria um painel de episódios de podcast dentro do WordPress. Cada episódio pode ter título, descrição, capa, URL de áudio, duração e status de exibição.

O plugin também oferece o shortcode [novacast_player] para exibir episódios no frontend com um player de áudio responsivo.

== Installation ==

1. Envie a pasta Novacast para wp-content/plugins/.
2. Ative o plugin no painel do WordPress.
3. Acesse Novacast > Adicionar novo para cadastrar episódios.
4. Use o shortcode [novacast_player] em uma página ou post.

== Shortcodes ==

Exibir os últimos episódios:

[novacast_player]

Exibir até 5 episódios:

[novacast_player limit="5"]

Exibir um episódio específico:

[novacast_player id="123"]

== Changelog ==

= 0.1.0 =
* Estrutura inicial do plugin.
* Cadastro de episódios como Custom Post Type.
* Campos de URL de áudio, duração e status ativo.
* Shortcode de player no frontend.
