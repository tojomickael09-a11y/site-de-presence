program GestionnaireContacts;

uses crt;

type
    { On définit une structure personnalisée }
    Contact = record
        nom: string[50];
        telephone: string[15];
    end;

var
    repertoire: array[1..100] of Contact;
    nbContacts, choix, i: integer;

{ Procédure pour ajouter un contact }
procedure AjouterContact;
begin
    if nbContacts < 100 then
    begin
        nbContacts := nbContacts + 1;
        writeln('--- Nouveau Contact ---');
        write('Nom : '); readln(repertoire[nbContacts].nom);
        write('Telephone : '); readln(repertoire[nbContacts].telephone);
        writeln('Contact ajoute avec succes !');
    end
    else
        writeln('Erreur : Repertoire plein !');
end;

{ Procédure pour afficher tous les contacts }
procedure AfficherContacts;
begin
    writeln('--- Liste des Contacts ---');
    if nbContacts = 0 then
        writeln('Le repertoire est vide.')
    else
    begin
        for i := 1 to nbContacts do
        begin
            writeln(i, '. ', repertoire[i].nom, ' - Tel: ', repertoire[i].telephone);
        end;
    end;
end;

{ Programme Principal }
begin
    nbContacts := 0;
    repeat
        clrscr;
        writeln('===== MENU REPERTOIRE =====');
        writeln('1. Ajouter un contact');
        writeln('2. Afficher les contacts');
        writeln('3. Quitter');
        write('Votre choix : ');
        readln(choix);

        case choix of
            1: AjouterContact;
            2: AfficherContacts;
            3: writeln('Au revoir !');
        else
            writeln('Choix invalide !');
        end;
        
        if choix <> 3 then
        begin
            writeln('Appuyez sur Entree pour continuer...');
            readln;
        end;
    until choix = 3;
end.




