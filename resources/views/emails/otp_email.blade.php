<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Votre code de vérification</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f9f9f9;
        }
        .container {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .header {
            color: #2c3e50;
            border-bottom: 2px solid #f1f1f1;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .otp-code {
            font-size: 28px;
            letter-spacing: 3px;
            color: #007bff;
            background: #f8f9fa;
            padding: 15px 20px;
            border-radius: 5px;
            margin: 20px 0;
            display: inline-block;
            font-weight: bold;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            font-size: 12px;
            color: #7f8c8d;
        }
        .button {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            display: inline-block;
            margin: 15px 0;
        }
        .logo {
            max-width: 150px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Remplacez par le logo de votre entreprise -->
        <img src="{{ asset('images/logo.png') }}" alt="Logo" class="logo">
        
        <div class="header">
            <h1>Bonjour {{ $firstname }},</h1>
        </div>
        
        <p>Merci de faire partie de notre communauté. Voici votre code de vérification pour accéder à votre compte :</p>
        
        <div class="otp-code">{{ $otp_code }}</div>
        
        <p>Ce code est valable pendant <strong>10 minutes</strong>. Pour des raisons de sécurité, ne le partagez avec personne.</p>
        
        <p>Si vous n'avez pas demandé ce code, veuillez ignorer cet email ou <a href="mailto:support@techfuturists.com">contacter notre support</a>.</p>
        
        <div class="footer">
            <p>© {{ $year }} TechFuturists. Tous droits réservés.</p>
            <p>
                <a href="https://techfuturists.com/confidentialite" style="color: #7f8c8d; text-decoration: none;">Politique de confidentialité</a> | 
                <a href="https://techfuturists.com/conditions" style="color: #7f8c8d; text-decoration: none;">Conditions d'utilisation</a>
            </p>
        </div>
    </div>
</body>
</html>
